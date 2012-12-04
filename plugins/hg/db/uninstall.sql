DROP TABLE IF EXISTS plugin_hg;

DELETE FROM service WHERE short_name='hg';
DELETE FROM reference_group WHERE reference_id=30;
DELETE FROM reference WHERE id=30;

