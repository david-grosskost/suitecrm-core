# db: the database of the instance (specific to suitecrm-core), see database/README.md.

DB_SCRIPTS := $(patsubst %/,%,$(dir $(lastword $(MAKEFILE_LIST))))/scripts

.PHONY: db@seed

# Seeders to run (with their dependencies): make db@seed SEEDERS="core/contacts"
db@seed:
	@$(DB_SCRIPTS)/db/seed.sh
