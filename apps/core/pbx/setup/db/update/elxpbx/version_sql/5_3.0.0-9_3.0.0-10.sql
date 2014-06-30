ALTER TABLE queue_member ADD COLUMN member_order INT UNSIGNED NOT NULL DEFAULT 0;
ALTER TABLE queue_member ADD UNIQUE KEY queue_ordered_interface (queue_name, member_order, interface);
ALTER TABLE queue_member DROP KEY queue_interface;
