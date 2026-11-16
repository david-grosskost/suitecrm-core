# extensions: the extensions of the running instance (specific to suitecrm-core).
# They live in Administration → Chargeur de module; these commands do the same
# from the terminal, with the packages the image carries.

EXTENSIONS_SCRIPTS := $(patsubst %/,%,$(dir $(lastword $(MAKEFILE_LIST))))/scripts

# Extensions to install or uninstall: make extensions@install SLUGS="pwa login-background"
SLUGS ?=
export SLUGS

.PHONY: extensions@list extensions@install extensions@uninstall

extensions@list:
	@$(EXTENSIONS_SCRIPTS)/extensions/run.sh list

extensions@install:
	@$(EXTENSIONS_SCRIPTS)/extensions/run.sh install

extensions@uninstall:
	@$(EXTENSIONS_SCRIPTS)/extensions/run.sh uninstall
