# i18n: French labels (specific to suitecrm-core).

I18N_SCRIPTS := $(patsubst %/,%,$(dir $(lastword $(MAKEFILE_LIST))))/scripts

.PHONY: i18n@missing

i18n@missing:
	@$(I18N_SCRIPTS)/i18n/missing.sh
