# Meta Keys

The Meta Keys field allows you to add arbitrary pieces of information to entries
identified by a user generated key. You can set default keys, and choose different
output types by key or named key. It attempts to provide some handy filtering hooks
for your datasources as well.

- Version: 0.9.3
- Date: 9th March 2011
- Requirements: Symphony 2.0.8 or newer, <http://github.com/symphonycms/symphony-2/>
- Author: Brendan Abbott, brendan@bloodbone.ws
- GitHub Repository: <http://github.com/brendo/field_metakeys>

## INSTALLATION

1. Upload the 'field_metakeys' folder in this archive to your Symphony 'extensions' folder.
2. Enable it by selecting the "Field: Meta Keys", choose Enable from the with-selected menu, then click Apply.
3. You can now add the "Meta Keys" field to your Sections.

## OPTIONS

### Section
#### Default Keys
You can set a number of default keys in the Section Editor, these will appear when you
first create an entry. You don't have to use them, they can be removed with the [x].

#### Validator
The usual Symphony validation applies to the Values of your Keys.

#### Required Field
This makes sure that you have at least one completed Pair, that is, the Key and Value is
filled.

### Datasources
#### Named Keys vs. Keys
You can choose between the named keys output, or just a generic key output for your XML.
A named key will use the name of the key as the node name, whereas generic will just list
each pair under a 'key' node.

#### Filtering
Best efforts have been made for these to support normal Symphony enumerators of `+`,`,` and `:`,
but please report any unusual behaviour!

##### `colour`
Normal default filtering without any `*:` conditions will search on keys. This will return all
the entries where a key of `colour` exists (whether it has a value or not).

##### `value: red`
This will return all entries where one Pair exists that has the value of red.

##### `key-equals: colour=red`
This will return all entries where the `Colour` key equals `red`. You can chain this as well with
`key-equals: colour=red, shape=blue` that will get all entries where the `Colour` is `red` and
the `Shape` is `blue`.

## CHANGE LOG

*0.9.3* (9th March 2011)

- Cleanup of extension for S2.2
- Added Romanian translation (thanks Vlad)

*0.9.2* (15th December 2010)

- Fix output error when only one key/value pair was added

*0.9.1* (10th November 2010)

- Few CSS Tweaks to make Meta Keys play better with other fields
- Fix Safari autofocus bug

*0.9* (11th October 2010)

- Initial Public Release