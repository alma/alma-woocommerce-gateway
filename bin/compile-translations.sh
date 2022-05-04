#!/bin/bash
# This script compile PO files to MO files
# and make links between different dialects

MSGFMT="`which msgfmt`"
ROOT_DIR="`dirname $0`/.."
TRANS_SRC_DIR="$ROOT_DIR/src/languages"
TRANS_EXT="po"
COMP_EXT="mo"
TRANS_PREFIX="alma-woocommerce-gateway"
DEBUG=0

TRANS_LOCALES_DEFINITIONS="
en_US=en_IE,en_GB
es_ES
pt_PT
it_IT
de_DE=de_AT
nl_NL=nl_BE
fr_FR=fr_BE,fr_LU
"

# {{{ function quit
#
quit() {
    echo
    echo -e "ERROR: $@"
    usage
    exit 1
}
export -f quit
# }}}
# {{{ function usage
#
usage() {
    echo
    echo "This script compiles PO files to MO files"
    echo "and makes links between different dialects"
    echo
    echo "USAGE: $0 [OPTIONS]"
    echo
    echo "WHERE OPTIONS ARE:"
    echo
    echo "   -h|--help    print this message and exit without error"
    echo "   -d|--debug   debug (very verbose) mode"
    echo
}
export -f usage
# }}}
# {{{ function debug
#
debug() {
    [[ $DEBUG -eq 1 ]] && echo -e "DEBUG: $@"
}
export -f debug
# }}}
# {{{ function compile
#
compile() {
    debug "Running compile('$1')"
    local locale="$1"
    [[ -z "$locale" ]] && quit "locale not provided"
    local locale_file="$TRANS_SRC_DIR/$TRANS_PREFIX-$locale.$TRANS_EXT"
    [[ ! -e $locale_file || -L $locale_file ]] && return 1
    debug "compiling $locale_file"
    $MSGFMT $locale_file -o ${locale_file/.$TRANS_EXT/.$COMP_EXT}
}
export -f compile
# }}}
# {{{ function make_links
#
make_links() {
    debug "Running make_links('$1', '$2')"
    local locale="$1"
    local locale_links="${2//,/ }"
    [[ "x$locale" == "x$locale_links" || -z "$locale_links" ]] && return 1
    for ext in $TRANS_EXT $COMP_EXT ; do
        local locale_file="$TRANS_SRC_DIR/$TRANS_PREFIX-$locale.$ext"
        [[ ! -e $locale_file ]] && continue

        [[ $ext == $COMP_EXT ]] && debug "Linking $locale_file to $locale_links"
        for locale_link in $locale_links ; do
            local link_file="$TRANS_SRC_DIR/$TRANS_PREFIX-$locale_link.$ext"
            [[ -e $link_file ]] && debug "rm $link_file" && rm $link_file
            [[ $ext == $COMP_EXT ]] && debug "Linking $link_file" && ln -s `basename $locale_file` $link_file
        done
    done
}
export -f make_links
# }}}

#{{{ GETTING ARGS
while [[ ! -z "$1" ]] ; do
    case $1 in
        -h|--help) usage ; exit ;;
        -d|--debug) DEBUG=1 ;;
        *) quit "'$1': unknown argument" ;;
    esac
    shift
done
# }}}

[[ -z "$MSGFMT" ]] && quit "ERROR: you must install gettext before using this script (msgfmt not found)"
[[ ! -d "$TRANS_SRC_DIR" ]] && quit "$TRANS_SRC_DIR not such directory"

for locale_def in $TRANS_LOCALES_DEFINITIONS ; do
    locale=${locale_def%%=*}
    locale_links="${locale_def##*=}"
    compile $locale \
    && make_links $locale $locale_links
done


exit
