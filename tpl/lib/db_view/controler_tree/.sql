SELECT
  B.id,
  B.parent_id,
  B.name
FROM branches B
<?print($where)?>
<?print($order)?>
<?print($limit)?>