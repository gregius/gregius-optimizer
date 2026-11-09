#!/usr/bin/env bash
set -euo pipefail

[ $# -ge 1 ] || { echo "usage: smoke-test.sh <base-url> [-k]"; exit 2; }
base="${1%/}"
curlopts=""
[ "${2:-}" = "-k" ] && curlopts="-k"

for f in llms.txt robots.txt; do
	code=$(curl $curlopts -sS -o /dev/null -w '%{http_code}' "$base/$f")
	ctype=$(curl $curlopts -sS -o /dev/null -w '%{content_type}' "$base/$f")
	[ "$code" = "200" ] || { echo "FAIL $f status=$code"; exit 1; }
	case "$ctype" in text/plain*) ;; *) echo "FAIL $f content-type=$ctype"; exit 1;; esac
	echo "ok $f $code $ctype"
done
