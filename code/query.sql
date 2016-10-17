CREATE OR REPLACE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY
DEFINER VIEW cb_dev_groots.`retailerproductquotation_gridview` AS select
`sp`.`subscribed_product_id` AS `subscribed_product_id`,`sp`.`base_product_id`
AS `base_product_id`,`bp`.`title` AS
`title`,bp.pack_size,bp.pack_unit,`sp`.`store_price` AS
`store_price`,`sp`.`store_offer_price` AS `store_offer_price`,`sp`.`quantity`
AS `quantity`,`s`.`store_name` AS
`store`,if(isnull(`rp`.`retailer_id`),0,`rp`.`retailer_id`) AS
`retailer_id`,if(isnull(`rp`.`effective_price`),' ',`rp`.`effective_price`) AS
`effective_price`,if(isnull(`rp`.`discount_per`),0,`rp`.`discount_per`) AS
`discount_price`,if(isnull(`rp`.`status`),' 1',`rp`.`status`) AS `status`,
md.media_url, md.thumb_url from (((`subscribed_product` `sp` join
`base_product` `bp` on((`bp`.`base_product_id` = `sp`.`base_product_id`)))
left join `store` `s` on((`s`.`store_id` = `sp`.`store_id`))) left join
`retailer_product_quotation` `rp` on((`rp`.`subscribed_product_id` =
`sp`.`subscribed_product_id`))) left join media md on
(md.base_product_id=bp.base_product_id) where ((`sp`.`status` = 1) and
(`bp`.`status` = 1) );




alter table cb_dev_groots.subscribed_product modify column subscribed_product_id int(11) unsigned NOT NULL AUTO_INCREMENT, modify column `base_product_id` int(11) unsigned NOT NULL, add index fk_sub_prod_1 (base_product_id), add CONSTRAINT fk_sub_prod_1 foreign key (base_product_id) REFERENCES cb_dev_groots.base_product(base_product_id);

CREATE TABLE cb_dev_groots.`product_prices` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `base_product_id` int(11) unsigned NOT NULL,
  `subscribed_product_id` int(11) unsigned  NOT NULL,
  `price` int(11) unsigned NOT NULL,
  `effective_date` date NOT NULL,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  CONSTRAINT uk_prod_price_1 UNIQUE (base_product_id,subscribed_product_id,price,effective_date),
  INDEX fk_prod_prices_1 (base_product_id),
  INDEX fk_prod_prices_2 (subscribed_product_id),
  CONSTRAINT fk_prod_prices_1 FOREIGN KEY (base_product_id) REFERENCES base_product(base_product_id),
  CONSTRAINT fk_prod_prices_2 FOREIGN KEY (subscribed_product_id) REFERENCES subscribed_product(subscribed_product_id)
) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8


/*delete from cb_dev_groots.store where store_id=1;


alter table cb_dev_groots.store  auto_increment=1;*/


CREATE TABLE cb_dev_groots.`warehouses` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) DEFAULT NULL,
  `details` text DEFAULT NULL,
  `address` varchar(300) DEFAULT NULL,
  `state` varchar(100) DEFAULT NULL,
  `city` varchar(100) NOT NULL,
  `locality` varchar(100) DEFAULT NULL,
  `pincode` varchar(100) DEFAULT NULL,
  `mobile_numbers` varchar(100) DEFAULT NULL,
  `telephone_numbers` varchar(100) DEFAULT NULL,
  `email` varchar(255) DEFAULT NULL,
  `status` tinyint(1) NOT NULL DEFAULT '1',
  `created_date` datetime DEFAULT NULL,
  `modified_date` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `image` varchar(250) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;


insert into cb_dev_groots.`warehouses` values (null, 'sector-5, warehouse', null, 'sector-5, warehouse', 'Haryana', 'Gurgaon', 'Sector-5', 122001, null, null, null, 1, now(),now(),null);

alter table cb_dev_groots.retailer add column allocated_warehouse_id int(11) unsigned NOT NULL DEFAULT 1, add index fk_retailer_1 (allocated_warehouse_id), add constraint fk_retailer_1 foreign key (allocated_warehouse_id) REFERENCES cb_dev_groots.`warehouses`(id);





CREATE TABLE cb_dev_groots.`addresses` (
  `id` int(10) NOT NULL AUTO_INCREMENT,
  entity_type enum('retailer', 'order') NOT NULL,
  `entity_id` int(11) NOT NULL,
  `address_line_1` text NOT NULL,
  `city` varchar(100) NOT NULL,
  `state` varchar(100) DEFAULT NULL,
  `pincode` varchar(11) DEFAULT NULL,
  phone varchar(100) DEFAULT NULL,
  `created_at` datetime NOT NULL,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8;




alter table groots_orders.order_header modify column `invoice_number` varchar(255) DEFAULT NULL

update groots_orders.order_header oh  left join cb_dev_groots.retailer r on r.id=oh.user_id  set oh.user_id=1 where r.id is null;


alter table groots_orders.order_header add column billing_address_id int(10) DEFAULT NULL, add column shipping_address_id int(10) default NULL, add index fk_order_1 (user_id), add index fk_order_2 (billing_address_id), add index fk_order_3 (shipping_address_id)


alter table groots_orders.order_header add CONSTRAINT fk_order_1 foreign key (user_id) REFERENCES cb_dev_groots.retailer(id);

  alter table groots_orders.order_header add CONSTRAINT fk_order_2 foreign key (billing_address_id) REFERENCES cb_dev_groots.addresses(id);

alter table groots_orders.order_header add CONSTRAINT fk_order_3 foreign key (shipping_address_id) REFERENCES cb_dev_groots.addresses(id);


alter table groots_orders.order_header add column warehouse_id int(11) unsigned NOT NULL DEFAULT 1, add index fk_order_4 (warehouse_id), add constraint fk_order_4 foreign key (warehouse_id) REFERENCES cb_dev_groots.`warehouses`(id);

update cb_dev_groots.store set store_name="GROOTS FOOD VENTURE PRIVATE LIMITED";

alter table cb_dev_groots.product_prices add column `store_price` decimal(12,2) NOT NULL DEFAULT '0.00' after subscribed_product_id;

alter table cb_dev_groots.product_prices change column price `store_offer_price` decimal(12,2) NOT NULL DEFAULT '0.00';

ALTER TABLE cb_dev_groots.product_prices   DROP INDEX uk_prod_price_1,   ADD UNIQUE KEY `uk_prod_price_1` (`base_product_id`,`subscribed_product_id`,`effective_date`)

alter table groots_orders.order_line add column  delivered_qty decimal(10,2) DEFAULT NULL AFTER product_qty;



alter table cb_dev_groots.retailer add column initial_payable_amount decimal(10,2) DEFAULT NULL, add COLUMN total_payable_amount decimal(10,2) DEFAULT NULL;

CREATE TABLE groots_orders.`retailer_payments` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `retailer_id` int(11)  NOT NULL,
  paid_amount decimal(10,2) NOT NULL DEFAULT 0,
  `date` date NOT NULL,
  payment_type enum('Cash', 'Cheque', 'DemandDraft', 'OnlineTransfer') NOT NULL DEFAULT 'Cash',
  cheque_no VARCHAR(256) DEFAULT NULL,
  comment TEXT DEFAULT NULL,
  created_at date NOT NULL,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  INDEX fk_rt_pm_1 (retailer_id),
  CONSTRAINT fk_rt_pm_1 FOREIGN KEY (retailer_id) REFERENCES cb_dev_groots.retailer(id)
) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8;

alter table groots_orders.`retailer_payments` add COLUMN  status SMALLINT(4) NOT NULL DEFAULT 1;



alter table cb_dev_groots.base_product ADD COLUMN pack_size_in_gm float DEFAULT 0 AFTER pack_unit;
update  cb_dev_groots.base_product set pack_size_in_gm=pack_size where pack_unit='gm';
update  cb_dev_groots.base_product set pack_size_in_gm=pack_size where pack_unit='g';
update  cb_dev_groots.base_product set pack_size_in_gm=pack_size*1000 where pack_unit='kg';
update  cb_dev_groots.base_product set pack_size_in_gm=pack_size*1000 where pack_unit='dozen';

update cb_dev_groots.store set store_name="GROOTS FOODS VENTURES PRIVATE LIMITED" where store_id=1;

--------------
collection
alter table cb_dev_groots.retailer add column collection_fulfilled boolean not null default false;

CREATE TABLE cb_dev_groots.collection_agent(
id int(11) NOT NULL ,
name varchar(255) NOT NULL,
PRIMARY KEY( id )
);


alter table cb_dev_groots.retailer add column collection_frequency enum('daily', 'weekly', 'fortnight', 'monthly', '45-days') default 'daily',
 add column due_date date default null ;



update cb_dev_groots.retailer set collection_frequency = 'monthly' where id in ('136');
update cb_dev_groots.retailer set collection_frequency = 'weekly' where id in ('108','117','121','122','123','126','127','131','133','134','137','139','149','151','156','179');
update cb_dev_groots.retailer set collection_frequency = 'fortnight' where id in ('99','101','135','138','140','152');


update cb_dev_groots.retailer set due_date = '2016-10-16' where collection_frequency = 'fortnight';
update cb_dev_groots.retailer set due_date = '2016-11-01' where collection_frequency = 'monthly';
update cb_dev_groots.retailer set due_date = '2016-10-10' where collection_frequency = 'weekly';
update cb_dev_groots.retailer set due_date = '2016-10-05' where collection_frequency = 'daily';


insert into cb_dev_groots.collection_agent values (1,'Trilok');
insert into cb_dev_groots.collection_agent values (2,'Ranveer');


alter table cb_dev_groots.retailer add column last_due_date date default null;
update cb_dev_groots.retailer set last_due_date = '2016-10-01' where collection_frequency = 'fortnight';
update cb_dev_groots.retailer set last_due_date = '2016-10-01' where collection_frequency = 'monthly';
update cb_dev_groots.retailer set last_due_date = '2016-10-03' where collection_frequency = 'weekly';
update cb_dev_groots.retailer set last_due_date = '2016-10-04' where collection_frequency = 'daily';




alter table cb_dev_groots.retailer add column last_due_payment decimal(10,2) DEFAULT null;
update cb_dev_groots.retailer set last_due_payment = total_payable_amount;
---------------------------------------------------------
alter table cb_dev_groots.retailer add column collection_agent_id int(11) DEFAULT null;
update cb_dev_groots.retailer set collection_agent_id = 1 where id in ('67','69','100','112','144','147','154','158','159','161','164','166','168','169','170','175','182','183','185','186','72','101','108','109','117','123','126','131','132','133','134','135','139','140','148','149','152','179');
update cb_dev_groots.retailer set collection_agent_id = 2 where id in ('114','124','129','130','141','142','143','145','146','150','153','155','157','162','163','165','167','171','172','173','174','180','184','68','99','102','121','122','127','136','138','148','156');

alter table cb_dev_groots.collection_agent add column warehouse_id int(11) not null;


alter table cb_dev_groots.retailer change last_due_payment due_payable_amount decimal(10,2) not null;
alter table cb_dev_groots.retailer modify due_payable_amount decimal(10,2) default 0;
alter table cb_dev_groots.retailer modify collection_agent_id int(11) not null default 0;
alter table cb_dev_groots.collection_agent add column status boolean not null default 1;
---------------------------------------------------------------------------------


update cb_dev_groots.warehouses set name = 'Basai, ggn' where id = 1;

alter table cb_dev_groots.retailer add column collection_center_id int(11) not null default 0;

insert into cb_dev_groots.warehouses values(3,'Head-Office',NULL,'Ghitorni','Delhi','Delhi','Delhi','110030',NULL,NULL,NULL,'1',CURRENT_TIMESTAMP, CURRENT_TIMESTAMP, NULL, 2);
update cb_dev_groots.retailer set collection_center_id = 1;
update cb_dev_groots.retailer set collection_center_id = 3 where collection_frequency in ('monthly', 'fortnight');  


--------------------------------------
warehouse

run warehouse.sql
in protected folder
php yiic migrate --migrationPath=user.migrations
-------------
update cb_dev_groots.users set email="admin@gogroots.com" where username='admin';

---------------
use cb_dev_groots
install rbac module -http://www.yiilearning.com/yii-user-authetication-authorization-module-tutorial/

-------------------------------

/*create auth items from rbac panels*/

INSERT INTO `AuthItem` VALUES ('SuperAdmin',2,'','',''),('RbacAssignmentEditor',1,'','',''),('WarehouseEditor',1,'','',''),('ProcurementEditor',1,'','',''),('InventoryEditor',1,'','',''),('TransferEditor',1,'','',''),('OrderEditor',1,'','',''),('PurchaseEditor',1,'','',''),('OrderViewer',0,'','',''),('InventoryViewer',0,'','',''),('TransferViewer',0,'','',''),('PurchaseViewer',0,'','',''),('ProcurementViewer',0,'','','');

INSERT INTO `AuthItemChild` VALUES ('InventoryEditor','InventoryViewer'),('OrderEditor','OrderViewer'),('ProcurementEditor','ProcurementViewer'),('PurchaseEditor','PurchaseViewer'),('SuperAdmin','ProcurementEditor'),('SuperAdmin','WarehouseEditor'),('TransferEditor','TransferViewer'),('WarehouseEditor','InventoryEditor'),('WarehouseEditor','OrderEditor'),('WarehouseEditor','PurchaseEditor'),('WarehouseEditor','TransferEditor');



insert into cb_dev_groots.users values (null, "warehouseEditor", md5("w@123"), "w@abc.com","", 0, 1, now(), now()), (null, "procurementEditor", md5("p@123"), "p@abc.com","", 0, 1, now(), now()), (null, "inventoryEditor", md5("w@123"), "i@abc.com","", 0, 1, now(), now()), (null, "transferEditor", md5("w@123"), "t@abc.com","", 0, 1, now(), now()), (null, "orderEditor", md5("w@123"), "o@abc.com","", 0, 1, now(), now()), (null, "purchaseEditor", md5("w@123"), "ps@abc.com","", 0, 1, now(), now()), (null, "orderViewer", md5("w@123"), "ov@abc.com","", 0, 1, now(), now()), (null, "inventoryViewer", md5("w@123"), "iv@abc.com","", 0, 1, now(), now()), (null, "transferViewer", md5("w@123"), "tv@abc.com","", 0, 1, now(), now()), (null, "purchaseViewer", md5("w@123"), "pv@abc.com","", 0, 1, now(), now()), (null, "procurementViewer", md5("w@123"), "procurev@abc.com","", 0, 1, now(), now());


insert into cb_dev_groots.users values (null, "warehouseEditorAzd", md5("w@123"), "wa@abc.com","", 0, 1, now(), now()), (null, "procurementEditorAzd", md5("pa@123"), "pa@abc.com","", 0, 1, now(), now()), (null, "inventoryEditorAzd", md5("w@123"), "ia@abc.com","", 0, 1, now(), now()), (null, "transferEditorAzd", md5("w@123"), "ta@abc.com","", 0, 1, now(), now()), (null, "orderEditorAzd", md5("w@123"), "oa@abc.com","", 0, 1, now(), now()), (null, "purchaseEditorAzd", md5("w@123"), "psa@abc.com","", 0, 1, now(), now()), (null, "orderViewerAzd", md5("w@123"), "ova@abc.com","", 0, 1, now(), now()), (null, "inventoryViewerAzd", md5("w@123"), "iva@abc.com","", 0, 1, now(), now()), (null, "transferViewerAzd", md5("w@123"), "tva@abc.com","", 0, 1, now(), now()), (null, "purchaseViewerAzd", md5("w@123"), "pva@abc.com","", 0, 1, now(), now()), (null, "procurementViewerAzd", md5("w@123"), "procureva@abc.com","", 0, 1, now(), now());



insert into cb_dev_groots.profiles select id, username, username from cb_dev_groots.users where id>1;



insert into cb_dev_groots.AuthAssignment(itemname, userid, bizrule) values ('WarehouseEditor', 2, "return $params['warehouse_id']==1;"), ('ProcurementEditor', 3, "return $params['warehouse_id']==1;"), ('InventoryEditor', 4, "return $params['warehouse_id']==1;"), ('TransferEditor', 5, "return $params['warehouse_id']==1;"),('OrderEditor',6, "return $params['warehouse_id']==1;"),  ('PurchaseEditor', 7,"return $params['warehouse_id']==1;"),('OrderViewer', 8,"return $params['warehouse_id']==1;") , ('InventoryViewer', 9,"return $params['warehouse_id']==1;"), ('TransferViewer', 10, "return $params['warehouse_id']==1;"), ('PurchaseViewer', 11, "return $params['warehouse_id']==1;"), ('ProcurementViewer', 12, "return $params['warehouse_id']==1;")  ;

insert into cb_dev_groots.AuthAssignment(itemname, userid, bizrule) values ('WarehouseEditor', 13, "return $params['warehouse_id']==2;"), ('ProcurementEditor', 14, "return $params['warehouse_id']==2;"), ('InventoryEditor', 15, "return $params['warehouse_id']==2;"), ('TransferEditor', 16, "return $params['warehouse_id']==2;"),('OrderEditor',17, "return $params['warehouse_id']==2;"),  ('PurchaseEditor', 18,"return $params['warehouse_id']==2;"),('OrderViewer', 19,"return $params['warehouse_id']==2;") , ('InventoryViewer', 20,"return $params['warehouse_id']==2;"), ('TransferViewer', 21, "return $params['warehouse_id']==2;"), ('PurchaseViewer',22, "return $params['warehouse_id']==2;"), ('ProcurementViewer', 23, "return $params['warehouse_id']==2;")  ;


update cb_dev_groots.base_product set popularity=1 where base_product_id in (select distinct(base_product_id) from groots_orders.order_line ol join groots_orders.order_header oh on oh.order_id=ol.order_id where oh.status="Delivered" GROUP BY ol.base_product_id having count(*) > 50)


-------------
copy parent items from test db to production
update base_product set status=0  where title like "% G2 %";

update  product_category_mapping pcm join  base_product bp  on bp.parent_id=pcm.base_product_id join product_category_mapping pcm2 on bp.base_product_id=pcm2.base_product_id  set pcm.category_id=pcm2.category_id;


-----------------------------------
insert into cb_dev_groots.vendors (name, date_of_onboarding, created_date) values ("vendor1", now(), now()), ("vendor2", now(), now());

insert into cb_dev_groots.vendors (name, date_of_onboarding, created_date, allocated_warehouse_id) values ("vendor1", now(), now(), 2), ("vendor2", now(), now(), 2);

alter table groots_orders.order_line add COLUMN received_quantity decimal(10,2) DEFAULT NULL;