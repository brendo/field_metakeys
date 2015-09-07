# Meta Keys

The Meta Keys field allows you to add arbitrary pieces of information to entries identified by a user generated key. You can set default keys, and choose different output types by key or named key. It attempts to provide some handy filtering hooks for your datasources as well.

## Installation

1. Upload the `/field_metakeys` folder to your Symphony `/extensions` folder.
2. Enable it by selecting the "Field: Meta Keys", choose Enable from the with-selected menu, then click Apply.
3. You can now add the "Meta Keys" field to your Sections.

## Options

### Section
#### Default Keys

You can set a number of default keys in the Section Editor, these will appear when you first create an entry. If the default keys aren't filled with values,they will be removed with the duplicator upon saving.

If you want to assign a default value to a key, use the `::` syntax. eg. `Colour::Red`, which will fill the Key with 'Colour' and the value with 'Red'.

You can also prefill multiple keys with commas, ie. `Colour::Red, Size::Medium`. If you need to have a value that includes a comma, escape it, eg. `Colour::Red\\, Green`

#### Validator

The usual Symphony validation applies to the Values of your Keys.

#### Required Field

This settings ensures that you at least one completed Pair, the Key and Value, is filled.

### Datasources
#### Named Keys vs. Keys

You can choose between the named keys output, or just a generic key output for your XML. A named key will use the name of the key as the node name, whereas generic will just list each pair under a 'key' node.

Consider the example of Colour: Red:

Normal mode:

```xml
  <field mode='normal'>
    <key handle='colour' name='Colour'>
      <value hande='red'>Red</value>
    </key>
  <field>
```

Named key mode:

```xml
  <field mode='named-keys'>
    <colour handle='colour' name='Colour'>
      <value handle='red'>Red</value>
    </colour>
  </field>
```

#### Filtering

Best efforts have been made for these to support normal Symphony enumerators of `+`,`,` and `:`, but please report any unusual behaviour!

##### Filter by key (default)

```yaml
colour
```

Normal default filtering without any `*:` conditions will search on keys. This will return all the entries where a key of `colour` exists (whether it has a value or not).

##### Filter by values

```yaml
value: red
```

This will return all entries where one pair exists that has the value of `red`.


##### Filter by exact key/value pair

```yaml
key-equals: colour=red
```

This will return all entries where the `Colour` key equals `red`. You can chain this as well with `key-equals: colour=red, shape=square` that will get all entries where the `Colour` is `red` and the `Shape` is `square`.

##### Filter by exact key/value pair

```yaml
key-contains: colour=red
```

This will return all entries where the `Colour` key contains the word `red`, e. g. it matches `red` in `blue, green, red`. You can chain this as well with `key-contains: colour=red, shape=square` that will get all entries where `Colour` contains the word `red` and `Shape` contains `square`.

##### Filter by value range

```yaml
key-ranges: 5..10
key-ranges: 5...
key-ranges: ...10
```

Return all entries where the value in between the given range. An additional third dot allows "more than" (`5...`) or "less than" (`...10`) queries.

## XMLImporter support

Since the `0.9.5` release, this Field now integrates with XMLImporter. It expects a comma delimited string, eg. `red, square`. This will populate the first key with `red` and the second key with `square`.

If there are default keys, these will be pre-filled first. So if the Default Keys for my Meta Keys field were `Colour, Shape`, the previous string would result in `Colour: red, Shape: square`. If the string contained more values than keys, additional keys are named as `Key $i`, where is `$i` is the index. So `red, square, $10.00` would result in `Colour: red, Shape: square, Key 3: $10.00`.
