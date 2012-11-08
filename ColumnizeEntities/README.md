Columnize Entities
==================

**Purpose**<br />
Output entities in columns

**Example**
```php
<style>
  .border { border: 1px solid black; }
</style>

<?php
$entities = array();
for ($i = 0; $i < 8; $i++) {
    $obj = new stdClass();
    $obj->name = 'Object' . $i;
    $obj->number = mt_rand(1, 1000);
    $entities[] = $obj;
}

$instance = new ColumnizeEntities();
$params = array(
    'cols' => 3,
    'entities' => $entities,
    'entityCallback' => function ($entity) {
        $output = sprintf(
            '<div align="center">%s<br />Number: %d</div>',
            $entity->name,
            $entity->number
        );
        return $output;
    },
    'tdClass' => 'border',
);
echo $instance($params);
?>
```
_BECOMES_
<!--
<style>
  .border { border: 1px solid black; }
</style>
-->
<table id="" class="" width="100%">
  <tr class="">
    <td class="border" width="33.333333333333%">
      <div align="center">Object0<br />Number: 909</div>
    </td>
    <td class="border" width="33.333333333333%">
      <div align="center">Object1<br />Number: 11</div>
    </td>
    <td class="border" width="33.333333333333%">
      <div align="center">Object2<br />Number: 482</div>
    </td>
  </tr>
  <tr class="">
    <td class="border" width="33.333333333333%">
      <div align="center">Object3<br />Number: 152</div>
    </td>
    <td class="border" width="33.333333333333%">
      <div align="center">Object4<br />Number: 433</div>
    </td>
    <td class="border" width="33.333333333333%">
      <div align="center">Object5<br />Number: 449</div>
    </td>
  </tr>
</table>
<table id="" class="" width="100%">
  <tr class="">
    <td class="border" width="50%">
      <div align="center">Object6<br />Number: 530</div>
    </td>
    <td class="border" width="50%">
      <div align="center">Object7<br />Number: 963</div>
    </td>
  </tr>
</table>