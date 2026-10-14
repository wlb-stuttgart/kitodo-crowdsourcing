<?php

// SPDX-FileCopyrightText: 2026 Württembergische Landesbibliothek
//
// SPDX-License-Identifier: GPL-3.0-or-later

declare(strict_types=1);

namespace Wlb\Crowdsourcing\Middleware;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Psr\Log\LoggerInterface;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Http\JsonResponse;
use TYPO3\CMS\Core\Http\RequestFactory;
use TYPO3\CMS\Core\Site\Entity\Site;
use TYPO3\CMS\Frontend\Authentication\FrontendUserAuthentication;
use Wlb\Crowdsourcing\Services\ExtensionConfigurationService;

/**
 * Server-side proxy for the lobid.org GND search used by the autocomplete in the metadata form.
 *
 * The client only sends the search term and the identifiers of the form field (process type and
 * metadata key). The lobid filter is taken exclusively from the stored metadata configuration,
 * all other request parameters are fixed, so the endpoint cannot be used to send arbitrary
 * queries to lobid.org.
 */
class LobidSearchMiddleware implements MiddlewareInterface
{
    public const PATH = 'api/crowdsourcing/lobid-search';

    private const LOBID_SEARCH_URL = 'https://lobid.org/gnd/search';
    private const RESULT_SIZE = 10;
    private const MIN_QUERY_LENGTH = 2;
    private const MAX_QUERY_LENGTH = 200;
    private const DEFAULT_USER_AGENT = 'Mitmachen-gnd';

    public function __construct(
        private readonly RequestFactory $requestFactory,
        private readonly ConnectionPool $connectionPool,
        private readonly LoggerInterface $logger
    ) {
    }

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $site = $request->getAttribute('site');
        if (!$site instanceof Site || $request->getUri()->getPath() !== self::getEndpointPath($site)) {
            return $handler->handle($request);
        }

        if ($request->getMethod() !== 'GET') {
            return $this->jsonError('Method not allowed', 405);
        }

        // Only logged-in frontend users may use the proxy
        $frontendUser = $request->getAttribute('frontend.user');
        if (!$frontendUser instanceof FrontendUserAuthentication || (int)($frontendUser->user['uid'] ?? 0) <= 0) {
            return $this->jsonError('Forbidden', 403);
        }

        $params = $request->getQueryParams();
        $processType = is_string($params['type'] ?? null) ? $params['type'] : '';
        $metadataKey = is_string($params['field'] ?? null) ? $params['field'] : '';
        $query = is_string($params['q'] ?? null) ? trim($params['q']) : '';

        $queryLength = mb_strlen($query);
        if ($queryLength < self::MIN_QUERY_LENGTH || $queryLength > self::MAX_QUERY_LENGTH) {
            return $this->jsonError('Invalid query', 400);
        }

        $fieldConfig = $this->getLobidFieldConfiguration($processType, $metadataKey);
        if ($fieldConfig === null) {
            return $this->jsonError('Unknown field', 404);
        }

        $lobidParams = [
            'q' => $query,
            'format' => 'json',
            'size' => self::RESULT_SIZE,
        ];
        $filter = trim((string)($fieldConfig['lobidFilter'] ?? ''));
        if ($filter !== '') {
            $lobidParams['filter'] = $filter;
        }

        try {
            $response = $this->requestFactory->request(
                self::LOBID_SEARCH_URL . '?' . http_build_query($lobidParams, '', '&', PHP_QUERY_RFC3986),
                'GET',
                [
                    'headers' => [
                        'User-Agent' => $this->getUserAgent(),
                        'Accept' => 'application/json',
                    ],
                    'timeout' => 10,
                    'connect_timeout' => 5,
                    'allow_redirects' => false,
                    'http_errors' => false,
                ]
            );
        } catch (\Throwable $e) {
            $this->logger->error('lobid request failed: ' . $e->getMessage());
            return $this->jsonError('lobid request failed', 502);
        }

        if ($response->getStatusCode() !== 200) {
            $this->logger->warning('lobid responded with status ' . $response->getStatusCode());
            return $this->jsonError('lobid request failed', 502);
        }

        $data = json_decode((string)$response->getBody(), true);
        $members = is_array($data) && is_array($data['member'] ?? null) ? array_values($data['member']) : [];

        return (new JsonResponse(['member' => $members]))
            ->withHeader('Cache-Control', 'private, no-store');
    }

    /**
     * Path of the proxy endpoint for the given site, used by the middleware and for rendering the URL.
     */
    public static function getEndpointPath(Site $site): string
    {
        return rtrim($site->getBase()->getPath(), '/') . '/' . self::PATH;
    }

    /**
     * Returns the configuration of the given metadata field, if lobid autocomplete is enabled for it.
     */
    private function getLobidFieldConfiguration(string $processType, string $metadataKey): ?array
    {
        if ($processType === '' || $metadataKey === '') {
            return null;
        }

        $queryBuilder = $this->connectionPool->getQueryBuilderForTable('tx_crowdsourcing_domain_model_metadataconfiguration');
        $json = $queryBuilder
            ->select('json')
            ->from('tx_crowdsourcing_domain_model_metadataconfiguration')
            ->orderBy('uid')
            ->setMaxResults(1)
            ->executeQuery()
            ->fetchOne();

        if (!is_string($json) || $json === '') {
            return null;
        }

        $config = json_decode($json, true);
        $fieldConfig = $config[$processType][$metadataKey] ?? null;

        if (!is_array($fieldConfig) || empty($fieldConfig['lobidEnabled'])) {
            return null;
        }

        return $fieldConfig;
    }

    private function getUserAgent(): string
    {
        try {
            $userAgent = trim((string)ExtensionConfigurationService::getInstance()->getConfigurationValue('lobidUserAgent'));
        } catch (\Throwable) {
            $userAgent = '';
        }

        return $userAgent !== '' ? $userAgent : self::DEFAULT_USER_AGENT;
    }

    private function jsonError(string $message, int $status): ResponseInterface
    {
        return (new JsonResponse(['error' => $message], $status))
            ->withHeader('Cache-Control', 'private, no-store');
    }
}
