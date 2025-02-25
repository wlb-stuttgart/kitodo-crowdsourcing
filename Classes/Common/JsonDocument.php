<?php

namespace Wlb\Crowdsourcing\Common;

use JsonPath\JsonObject;

class JsonDocument
{
    protected $data;

    /**
     * @param string $data
     */
    public function __construct($data)
    {
        $this->data = new JsonObject($data);
    }

    /**
     * @param string $path JSONPath expression
     * @return JsonDocument[]
     * @throws \JsonPath\InvalidJsonException
     */
    public function findByJsonPath(string $path)
    {
        $result = $this->data->get($path);

        $jsonDocuments = [];

        if (isset($result[0]) && !empty($result[0])) {

            if (is_array($result[0])) {
            foreach ($result[0] as $value) {
                if (!empty($value)) {
                    $value = json_encode($value);
                    if ($value !== false) {
                        $jsonDocuments[] = new JsonDocument($value);
                    }
                }
            }
            } else {
                if (!empty($result[0])) {
                    $value = json_encode($result[0]);
                    if ($value !== false) {
                        $jsonDocuments[] = new JsonDocument($value);
                    }
                }

            }

        }

        return $jsonDocuments;
    }

    public function toJson() {
        return $this->data->getJson();
    }

    /**
     * @return array
     */
    public function toString() {
        if (is_array($this->data->getValue())) {
            return $this->flattenArrayToString($this->data->getValue());
        } else {
            return $this->data->getValue();
        }
    }

    protected function flattenArrayToString($array) {
        $flattened = [];
        foreach ($array as $value) {
            if (is_array($value)) {
                $flattened = array_merge($flattened, $this->flattenArrayToString($value));
            } else {
                $flattened[] = $value;
            }
        }
        return implode(',', $flattened);
    }
}
