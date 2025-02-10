CREATE TABLE tx_crowdsourcing_domain_model_campaign (
    title       varchar(255)     DEFAULT ''  NOT NULL,
    description text             DEFAULT ''
);

CREATE TABLE tx_crowdsourcing_domain_model_process (
    title       varchar(255)    DEFAULT ''  NOT NULL,
    identifier  int,
    images      text,
    state       varchar(255),
    metadata    int(11)         DEFAULT '0' NOT NULL
);

CREATE TABLE tx_crowdsourcing_domain_model_metadata (
    name        varchar(255)    DEFAULT ''  NOT NULL,
    value       text,
    process     int(11)         DEFAULT '0' NOT NULL
);

CREATE TABLE tx_crowdsourcing_domain_model_metadataconfiguration (
    data        text
);
