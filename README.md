# Event Sourcing

This library is for now an purely educational project.

Idea behind this project is not to make assomptions about the user own business
but still provide a set of optional features to help domain-driven development.

It will not implement a complete domain-driven development API, lots of libraries
already exist to implement every bits, such as Symfony's Messenger component for
communication buses.

If you are looking for a stable, production-ready, and mature alternative, please
look at https://github.com/prooph/ instead. It has a complete coverage for the
domain-driven, CQRS and Event Sourcing based devleopment, with a very well
performing, very stable, and very well documented set of libraries.


# Status

It implements the following:

 * complete and functionnal event store interface with store and query abilities,
 * in-memory array-based event store implementation allowing test-driven development,
 * https://github.com/pounard/goat database connector implementation fully working with
   PDO MySQL, PDO PgSQL and ext_pgsql drivers.


# Status

For now, this is a purely educational project, but if it ends up being performant
and well tested enough, it might be one day a full-fledged production-ready project.


# Roadmap

Wish-list, in order of preference and needs:

 * [x] add a 'type' column for aggregate identity, with no constraint at the store level,
 * [ ] test it even more,
 * [ ] implement a basic event emitter and event listener API,
 * [x] implement a basic aggregate-based event-driven domain oriented API layer implemented using generic store factory decorators,
 * [ ] very large volume benchmarking,
 * [ ] configuration-based event namespace storage partionning API implemented using generic store factory decorators,
 * [ ] transaction support if the other databases are using the same driver as the event store,
 * [ ] published/failed status in event table,
 * [x] Symfony bundle with a few dependency injection passes in order to register repositories,
 * [ ] Symfony bundle: find a way to properly register or configure event store backend,
 * [ ] Symfony bundle: provide configuration for namespaces,
 * [ ] better configuration abilities for goat factory,
 * [x] snapshort storage and API,
 * [ ] better serializer API for snapshot storage
 * [ ] auto serializer instanciation depending on the event store
 * [ ] helpers to use it along Symfony's Messenger component,
 * [ ] provide more event store backends,
 * [ ] Drupal 7 driver for event store,
 * [ ] Drupal 8 driver for event store.

# Additional arbitrary notes from concrete code

<pre>
 *   - store in-memory event using a decorator over the event store/event store factory
 *     - if so, wrap with a transaction
 *     - write everything when it goes OK
 *   - namespace in event store should be an instance of Namespace object
 *      - if object is dynamic (tokens with, for example, date or able to partition)
 *        then the real end namespace will be dynamically computed on each run
 *      - NOT sure this is a good idea
 *   - write a generic implementation of aggregate that self-defines it fields (such as
 *     drupal 8 entity system)
 *       - use Symfony validation component for validation, base dynamic field definition
 *         upon object internal properties
 *       - allow read-only and read-write properties
 *       - define generic update event and when() handler that supports it with a dynamic
 *         event name
 *       - define a generic command implementation for updates that check for
 *         allowed values
 *       - define a generic handler implementation that validates the allowed values
</pre>
