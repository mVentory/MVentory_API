
--
-- This will delete every attribute Set which has 0 products assigned to it.
-- Set with id 4 is the Default set.
--
DELETE
FROM eav_attribute_set
WHERE attribute_set_id NOT IN (SELECT attribute_set_id FROM catalog_product_entity) 
AND entity_type_id=4
AND attribute_set_id !=4;

--
-- This will delete attributes that are unassigned in every Set.
-- If the attribute is assigned in one set, it will not be deleted.
--
DELETE ea
FROM eav_attribute ea LEFT JOIN eav_entity_attribute eea ON ea.attribute_id=eea.attribute_id
WHERE ea.entity_type_id=4 AND ea.is_user_defined=1
AND ea.attribute_id NOT IN (SELECT attribute_id FROM eav_entity_attribute);  /* attr is not assigned in any set */