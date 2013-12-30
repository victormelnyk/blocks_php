SELECT
  T.id,
  T.f_boolean,
  T.f_tinyint,
  T.f_integer,
  T.f_varchar,
  T.f_text,
  T.f_money,
  T.f_float,
  T.f_datetime
FROM types T
<?print($where)?>
<?print($order)?>
<?print($limit)?>