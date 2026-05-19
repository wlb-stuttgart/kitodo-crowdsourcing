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
    fe_user             int(11)         DEFAULT '0' NOT NULL,
    last_accessed       int(11)         DEFAULT '0' NOT NULL
);

CREATE TABLE tx_crowdsourcing_domain_model_processhistory (
    title               varchar(255)    DEFAULT ''  NOT NULL,
    record_identifier   varchar(255),
    images              text,
    state               varchar(255),
    type                varchar(255),
    metadata            MEDIUMTEXT      DEFAULT '0' NOT NULL,
    campaign            int(11)         DEFAULT '0' NOT NULL,
    fe_user             int(11)         DEFAULT '0' NOT NULL,
    last_accessed       int(11)         DEFAULT '0' NOT NULL
);

CREATE TABLE tx_crowdsourcing_domain_model_metadataconfiguration (
    name        varchar(255)    DEFAULT '',
    json        LONGTEXT
);

#
# Table structure for table 'fe_users'
#
CREATE TABLE fe_users (
    consent_publish_username_stats tinyint(1) unsigned DEFAULT '0' NOT NULL,
    consent_publish_username_edits tinyint(1) unsigned DEFAULT '0' NOT NULL
);

CREATE TABLE tx_crowdsourcing_domain_model_clickstatistic (
    uid int(11) NOT NULL auto_increment,
    pid int(11) DEFAULT '0' NOT NULL,
    
    tstamp int(11) unsigned DEFAULT '0' NOT NULL,
    crdate int(11) unsigned DEFAULT '0' NOT NULL,
    
    user_agent text,
    fe_user_uid int(11) DEFAULT '0' NOT NULL,
    
    action_type varchar(30) DEFAULT '' NOT NULL,
    action_identifier varchar(30) DEFAULT '' NOT NULL,
    
    uri text,
    referrer text,
    
    process_uid int(11) DEFAULT '0' NOT NULL,
    process_state varchar(20) DEFAULT '' NOT NULL,
    campaign_uid int(11) DEFAULT '0' NOT NULL,
    
    session_id varchar(255) DEFAULT '' NOT NULL,
    
    additional_data text,
    
    PRIMARY KEY (uid),
    KEY parent (pid),
    KEY fe_user_uid (fe_user_uid),
    KEY process_uid (process_uid),
    KEY campaign_uid (campaign_uid),
    KEY action_type (action_type),
    KEY tstamp (tstamp),
    -- Index for statistics calculation of the average processing time
    KEY idx_cs_partition (deleted, action_type, process_uid, fe_user_uid, process_state, crdate, uid, action_identifier),
    KEY idx_cs_save_lookup (action_identifier, action_type, deleted, process_uid, process_state, fe_user_uid),
    -- Index for statistics calculation of the average dwell time
    KEY idx_cs_user_sessions (deleted, fe_user_uid, crdate),
    -- Index for statistics calculation of the monthly page views of a given year
    KEY idx_cs_crdate_action (deleted, crdate, action_type, action_identifier, fe_user_uid)
);
