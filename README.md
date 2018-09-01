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

 * test it even more,

 * implement a basic event emitter and event listener API,

 * implement a basic aggregate-based event-driven domain oriented API layer
   implemented using generic store factory decorators,

 * very large volume benchmarking,

 * configuration-based event namespace storage partionning API implemented using
   generic store factory decorators,

 * transaction support if the other databases are using the same driver as the
   event store,

 * published/failed status in event table,

 * Symfony light bundle with a few dependency injection passes (also would be
   working for Drupal 8) in order to register events and aggregates,

 * better configuration abilities for goat factory,

 * snapshort storage and API,

 * helpers to use it along Symfony's Messenger component,

 * Drupal 7 driver for event store,

 * Drupal 8 driver for event store.
