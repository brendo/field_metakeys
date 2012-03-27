# Meta Keys

The Meta Keys field allows you to add arbitrary pieces of information to entries identified by a user generated key. You can set default keys, and choose different output types by key or named key. It attempts to provide some handy filtering hooks for your datasources as well.

- Version: 1.0
- Date: 27th March 2012
- Requirements: Symphony 2.3 or newer, <http://github.com/symphonycms/symphony-2/>
- Author: Brendan Abbott, brendan@bloodbone.ws
- GitHub Repository: <http://github.com/brendo/field_metakeys>

## INSTALLATION

1. Upload the `/field_metakeys` folder to your Symphony `/extensions` folder.
2. Enable it by selecting the "Field: Meta Keys", choose Enable from the with-selected menu, then click Apply.
3. You can now add the "Meta Keys" field to your Sections.

## OPTIONS

### Section
#### Default Keys
You can set a number of default keys in the Section Editor, these will appear when you first create an entry. You don't have to use them, they can be removed with the [x].

#### Validator
The usual Symphony validation applies to the Values of your Keys.

#### Required Field
This makes sure that you have at least one completed Pair, that is, the Key and Value is filled.

### Datasources
#### Named Keys vs. Keys
You can choose between the named keys output, or just a generic key output for your XML. A named key will use the name of the key as the node name, whereas generic will just list each pair under a 'key' node.

#### Filtering
Best efforts have been made for these to support normal Symphony enumerators of `+`,`,` and `:`, but please report any unusual behaviour!

##### `colour`
Normal default filtering without any `*:` conditions will search on keys. This will return all the entries where a key of `colour` exists (whether it has a value or not).

##### `value: red`
This will return all entries where one Pair exists that has the value of red.

##### `key-equals: colour=red`
This will return all entries where the `Colour` key equals `red`. You can chain this as well with `key-equals: colour=red, shape=square` that will get all entries where the `Colour` is `red` and the `Shape` is `square`.

## XMLImporter support

Since the `0.9.5` release, this Field now integrates with XMLImporter. It expects a comma delimited string, eg. `red, square`. This will populate the first key with `red` and the second key with `square`.

If there are default keys, these will be pre-filled first. So if the Default Keys for my Meta Keys field were `Colour, Shape`, the previous string would result in `Colour: red, Shape: square`. If the string contained more values than keys, additional keys are named as `Key $i`, where is `$i` is the index. So `red, square, $10.00` would result in `Colour: red, Shape: square, Key 3: $10.00`.