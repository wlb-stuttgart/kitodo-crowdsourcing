CREATE TABLE tx_crowdsourcing_domain_model_campaign (
    title       varchar(255)     DEFAULT ''  NOT NULL,
    description text             DEFAULT ''
);

CREATE TABLE tx_crowdsourcing_domain_model_process (
    title       varchar(255)    DEFAULT ''  NOT NULL,
    identifier  varchar(255),
    images      text,
    state       varchar(255),
    metadata    MEDIUMTEXT      DEFAULT '0' NOT NULL
);

CREATE TABLE tx_crowdsourcing_domain_model_metadataconfiguration (
    data        text
);
