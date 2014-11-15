SELECT
  B.id,
  B.parent_id,
  B.name
FROM branches B
<?p($where)?>
<?p($order)?>
<?p($limit)?>