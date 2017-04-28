#
# Table structure for table 'tx_mksearch_indices'
#
CREATE TABLE tx_mksearch_indices (
    uid int(11) NOT NULL auto_increment,
    pid int(11) DEFAULT '0' NOT NULL,
    tstamp int(11) DEFAULT '0' NOT NULL,
    crdate int(11) DEFAULT '0' NOT NULL,
    cruser_id int(11) DEFAULT '0' NOT NULL,
    sys_language_uid int(11) DEFAULT '0' NOT NULL,
    l10n_parent int(11) DEFAULT '0' NOT NULL,
    l10n_diffsource mediumtext,
    deleted tinyint(4) DEFAULT '0' NOT NULL,
    hidden tinyint(4) DEFAULT '0' NOT NULL,
    title varchar(100) DEFAULT '' NOT NULL,
    description text,
    name varchar(255) DEFAULT '0' NOT NULL,
    engine varchar(50) DEFAULT '0' NOT NULL,
    solrversion int(11) DEFAULT '0' NOT NULL,
    composites int(11) DEFAULT '0' NOT NULL,
    configuration text,
    PRIMARY KEY (uid),
    KEY parent (pid)
);

#
# Table structure for table 'tx_mksearch_configcomposites'
#
CREATE TABLE tx_mksearch_configcomposites (
    uid int(11) NOT NULL auto_increment,
    pid int(11) DEFAULT '0' NOT NULL,
    tstamp int(11) DEFAULT '0' NOT NULL,
    crdate int(11) DEFAULT '0' NOT NULL,
    cruser_id int(11) DEFAULT '0' NOT NULL,
    sys_language_uid int(11) DEFAULT '0' NOT NULL,
    l10n_parent int(11) DEFAULT '0' NOT NULL,
    l10n_diffsource mediumtext,
    deleted tinyint(4) DEFAULT '0' NOT NULL,
    hidden tinyint(4) DEFAULT '0' NOT NULL,
    title varchar(100) DEFAULT '' NOT NULL,
    description text,
    configuration text,
    indices int(11) DEFAULT '0' NOT NULL,
    configs int(11) DEFAULT '0' NOT NULL,
    PRIMARY KEY (uid),
    KEY parent (pid)
);

#
# Table structure for table 'tx_mksearch_indices_configcomposites_mm'
#
#
CREATE TABLE tx_mksearch_indices_configcomposites_mm (
  uid_local int(11) DEFAULT '0' NOT NULL,
  uid_foreign int(11) DEFAULT '0' NOT NULL,
  tablenames varchar(30) DEFAULT '' NOT NULL,
  sorting int(11) DEFAULT '0' NOT NULL,
  sorting_foreign  int(11) DEFAULT '0' NOT NULL,
  KEY uid_local (uid_local),
  KEY uid_foreign (uid_foreign)
);

#
# Table structure for table 'tx_mksearch_indexerconfigs'
#
CREATE TABLE tx_mksearch_indexerconfigs (
    uid int(11) NOT NULL auto_increment,
    pid int(11) DEFAULT '0' NOT NULL,
    tstamp int(11) DEFAULT '0' NOT NULL,
    crdate int(11) DEFAULT '0' NOT NULL,
    cruser_id int(11) DEFAULT '0' NOT NULL,
    sys_language_uid int(11) DEFAULT '0' NOT NULL,
    l10n_parent int(11) DEFAULT '0' NOT NULL,
    l10n_diffsource mediumtext,
    deleted tinyint(4) DEFAULT '0' NOT NULL,
    hidden tinyint(4) DEFAULT '0' NOT NULL,
    title varchar(100) DEFAULT '' NOT NULL,
    description text,
    extkey varchar(50) DEFAULT '' NOT NULL,
    contenttype varchar(50) DEFAULT '' NOT NULL,
    configuration text,
    composites int(11) DEFAULT '0' NOT NULL,
    PRIMARY KEY (uid),
    KEY parent (pid)
);

#
# Table structure for table 'tx_mksearch_configcomposites_indexerconfigs_mm'
#
#
CREATE TABLE tx_mksearch_configcomposites_indexerconfigs_mm (
  uid_local int(11) DEFAULT '0' NOT NULL,
  uid_foreign int(11) DEFAULT '0' NOT NULL,
  tablenames varchar(30) DEFAULT '' NOT NULL,
  sorting int(11) DEFAULT '0' NOT NULL,
  sorting_foreign  int(11) DEFAULT '0' NOT NULL,
  KEY uid_local (uid_local),
  KEY uid_foreign (uid_foreign)
);


--
-- Tabellenstruktur für Tabelle `tx_mksearch_queue`
--
CREATE TABLE tx_mksearch_queue (
    uid int(11) NOT NULL auto_increment,
    cr_date datetime default '0000-00-00 00:00:00',
    lastupdate datetime default '0000-00-00 00:00:00',
    deleted tinyint(4) DEFAULT '0' NOT NULL,

    prefer tinyint(4) DEFAULT '0' NOT NULL,
    recid int(11) DEFAULT '0' NOT NULL,
    tablename varchar(255) DEFAULT '' NOT NULL,
    data text,
    resolver varchar(255) NOT NULL default '',

    PRIMARY KEY (uid)
);

#
# Table structure for table 'tx_mksearch_keywords'
#
CREATE TABLE tx_mksearch_keywords (
    uid int(11) NOT NULL auto_increment,
    pid int(11) DEFAULT '0' NOT NULL,
    tstamp int(11) DEFAULT '0' NOT NULL,
    crdate int(11) DEFAULT '0' NOT NULL,
    cruser_id int(11) DEFAULT '0' NOT NULL,
    deleted tinyint(4) DEFAULT '0' NOT NULL,
    hidden tinyint(4) DEFAULT '0' NOT NULL,
    keyword tinytext NOT NULL,
    link tinytext NOT NULL,

    PRIMARY KEY (uid),
    KEY parent (pid)
);

#
# Table structure for table 'tt_content'
#
CREATE TABLE tt_content (
    tx_mksearch_is_indexable tinyint(1) DEFAULT '0' NOT NULL
);
