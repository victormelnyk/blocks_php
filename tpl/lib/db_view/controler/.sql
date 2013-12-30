SELECT
  T.id,
  T.f_boolean,
  T.f_tinyint,
  T.f_integer,
  T.f_varchar,
  T.f_text,
  T.f_money,
  T.f_float,
  T.f_datetime,
  (
    SELECT 1
    FROM types
    WHERE id = 1
    LIMIT 20
  ) AS calculated
FROM types T
  LEFT JOIN (
    SELECT _T.id
    FROM types _T
    WHERE 2 = 2
    LIMIT 1
  ) AS _T ON _T.id = T.id
<?print($where)?>
<?print($order)?>
<?print($limit)?>