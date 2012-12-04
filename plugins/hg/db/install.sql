DROP TABLE IF EXISTS plugin_git;


CREATE TABLE IF NOT EXISTS `plugin_git` (
  `repository_id` int(10) unsigned NOT NULL auto_increment,
  `repository_name` varchar(255) NOT NULL,
  `repository_description` text,
  `repository_path` varchar(255) NOT NULL,
  `repository_parent_id` int(11) default NULL,
  `project_id` int(11) NOT NULL default '0',
  `repository_creation_user_id` int(11) NOT NULL,
  `repository_creation_date` datetime NOT NULL,
  `repository_deletion_date` datetime NOT NULL,
  `repository_is_initialized` tinyint(4) NOT NULL default '0',
  `repository_access` varchar(255) NOT NULL default 'private',
  PRIMARY KEY  (`repository_id`),
  KEY `project_id` (`project_id`)
);

-- Enable service for project 100
INSERT INTO service(group_id, label, description, short_name, link, is_active, is_used, scope, rank) 
       VALUES      ( 100, 'plugin_git:service_lbl_key', 'plugin_git:service_desc_key', 'git', '/plugins/git/?group_id=$group_id', 1, 0, 'system', 230 );

-- Create service for all other projects (but disabled)
INSERT INTO service(group_id, label, description, short_name, link, is_active, is_used, scope, rank)
  SELECT DISTINCT group_id, 'plugin_git:service_lbl_key', 'plugin_git:service_desc_key', 'git', CONCAT('/plugins/git/?group_id=', group_id), 1, 0, 'system', 230
        FROM service
        WHERE group_id NOT IN (SELECT group_id
                               FROM service
                               WHERE short_name
                               LIKE 'git');

        
INSERT INTO reference (id, keyword, description, link, scope, service_short_name, nature)
VALUES (30, 'git', 'plugin_git:reference_commit_desc_key', '/plugins/git/index.php/$group_id/view/$1/?a=commit&h=$2', 'S', 'git', 'git_commit');


INSERT INTO reference_group (reference_id, group_id, is_active)
SELECT 30, group_id, 1 FROM groups WHERE group_id;

