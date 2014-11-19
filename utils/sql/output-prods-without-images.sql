# This script selects a list of products where there is no small or base images
# selected.
#
# This includes the following:
# - no images at all
# - missing a selected small or base image
# - missing selected and not excluded small or base image

# NOTE, THE ABOVE IS A LIE BECAUSE THE SCRIPT DOES SOMETHING COMPLETELY DIFFERENT
# and a moral of the story is, if you write down what you want then there
# is a better chance that what you deliver is what you actually wanted

SELECT p.entity_id as id,
       p.type_id as type,
       p.updated_at as updated_at
FROM catalog_product_entity as p,
     cataloginventory_stock_item as s,
     catalog_product_entity_int as vis,
     catalog_product_entity_varchar as v
WHERE p.entity_id = s.product_id
  AND p.entity_id = vis.entity_id
  AND p.entity_id = v.entity_id
  AND s.is_in_stock = 1 #only products in stock are included
  AND vis.attribute_id = 91 #attribute code?
  AND vis.value != 1 #does it catch nulls? are nulls possible?
  AND v.attribute_id in (74,75,76) #attrubute codes?
  AND v.value = 'no_selection'
GROUP BY p.entity_id
HAVING count(*) <= 3 #I don't understand why you need this. Wouldn't be more
                     #reliable to look for absent data rather
                     #than a wrong total?
ORDER BY updated_at ASC
;
