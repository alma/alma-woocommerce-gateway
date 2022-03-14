#!/bin/bash
MSGFMT="`which msgfmt`"
if [[ -z "$MSGFMT" ]] ; then
    echo "ERROR: you must install gettext before using this script"
fi

for file in src/languages/*.po ; do
    if [ -e $file -a ! -L $file ] ; then
        $MSGFMT $file -o ${file/.po/.mo}
    fi
done

for file in src/languages/*BE.{po,mo} ; do
    [[ -e $file ]] && rm $file
done

for file in src/languages/*{NL,FR}.mo ; do
    be_file="`echo "$file" | sed 's/NL/BE/;s/FR/BE/'`"
    ln -s `basename $file` $be_file
done
