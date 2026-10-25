#!/usr/bin/env bash
# Locales and base URLs of the end-to-end scenario of Roni_CmsHreflang.   configure.sh <on|off>
#
#   on : "Add Store Code to Urls" = Yes. The store views of a website share its host: http://localhost/pt/, http://localhost/es/ ...
#   off: "Add Store Code to Urls" = No.  Every store view has its own host:            http://pt.localhost/, http://es.localhost/ ...
#
# Locales: default and b_en en_US, pt and (below) pt_BR, es es_ES, fr fr_FR, b_pt pt_PT. Website b_site is http://hreflang-b.localhost/.
# Run it where bin/magento works (inside the php-fpm container) after seed-stores.php. MAGENTO_CLI overrides the command.
set -euo pipefail
MODE="${1:?usage: configure.sh on|off}"
MAGENTO="${MAGENTO_CLI:-bin/magento}"
run() { $MAGENTO "$@" >/dev/null; }

for pair in default:en_US pt:pt_BR es:es_ES fr:fr_FR b_en:en_US b_pt:pt_PT; do
  run config:set --scope=stores --scope-code="${pair%%:*}" general/locale/code "${pair##*:}"
done
for path in web/unsecure/base_url web/secure/base_url; do
  run config:set --scope=websites --scope-code=base "$path" http://localhost/
  run config:set --scope=websites --scope-code=b_site "$path" http://hreflang-b.localhost/
done

# Store view level base URLs: the website host in the "on" scenario, a host of its own in the "off" one
for pair in pt:pt es:es fr:fr b_pt:b-pt; do
  code="${pair%%:*}"
  case "$code:$MODE" in
    b_pt:on) host=hreflang-b.localhost ;;
    *:on) host=localhost ;;
    *) host="${pair##*:}.localhost" ;;
  esac
  for path in web/unsecure/base_url web/secure/base_url; do
    run config:set --scope=stores --scope-code="$code" "$path" "http://$host/"
  done
done
run config:set web/url/use_store "$([ "$MODE" = on ] && echo 1 || echo 0)"
run config:set --scope=websites --scope-code=base roni_cms_hreflang/general/include_other_websites 0
run cache:flush
echo "configured: Add Store Code to Urls = $MODE"
