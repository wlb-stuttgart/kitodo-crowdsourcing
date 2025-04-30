CREATE TABLE tx_crowdsourcing_domain_model_campaign (
    title             varchar(255)  DEFAULT ''  NOT NULL,
    subtitle          varchar(255)  DEFAULT ''  NOT NULL,
    description       text          DEFAULT '',
    workflow_state    varchar(255)  DEFAULT ''  NOT NULL,
    short_description varchar(1024) DEFAULT ''  NOT NULL,
    processes         int(11)       DEFAULT '0' NOT NULL,
    image             int(11) UNSIGNED DEFAULT '0' NOT NULL
);

CREATE TABLE tx_crowdsourcing_domain_model_process (
    title               varchar(255)    DEFAULT ''  NOT NULL,
    record_identifier   varchar(255),
    images              text,
    state               varchar(255),
    type                varchar(255),
    metadata            MEDIUMTEXT      DEFAULT '0' NOT NULL,
    campaign            int(11)         DEFAULT '0' NOT NULL,
    fe_user             int(11)         DEFAULT '0' NOT NULL
);

CREATE TABLE tx_crowdsourcing_domain_model_processhistory (
    title               varchar(255)    DEFAULT ''  NOT NULL,
    record_identifier   varchar(255),
    images              text,
    state               varchar(255),
    type                varchar(255),
    metadata            MEDIUMTEXT      DEFAULT '0' NOT NULL,
    campaign            int(11)         DEFAULT '0' NOT NULL,
    fe_user             int(11)         DEFAULT '0' NOT NULL
);

CREATE TABLE tx_crowdsourcing_domain_model_metadataconfiguration (
    name        varchar(255)    DEFAULT '',
    json        JSON
);

#
# Table structure for table 'fe_users'
#
CREATE TABLE fe_users (
    extending varchar(60) DEFAULT '',
);