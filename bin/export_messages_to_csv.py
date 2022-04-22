#! /usr/bin/env python3


import csv
import getopt
import sys

import polib


def main(argv):
    output_file = ""
    try:
        opts, args = getopt.getopt(argv, "o:", ["ofile="])
    except getopt.GetoptError:
        print("./scripts/export_messages_to_csv.py -o <output_file>")
        sys.exit(2)
    for opt, arg in opts:
        if opt == "-h":
            print("./scripts/export_messages_to_csv.py -o <output_file>")
            sys.exit()
        elif opt in ("-o", "--ofile"):
            output_file = arg

    filepath = "./src/languages/alma-woocommerce-gateway.pot"

    po = polib.pofile(filepath)

    with open(output_file, "w") as csv_file:
        writer = csv.writer(csv_file, delimiter=";", quotechar='"')
        # Write headers
        writer.writerow(
            [
                "origin",
                "occurrences",
                "old_context",
                "new_context",
                "string",
                "string_plural",
            ]
        )

        for entry in po:
            occurrences = "\n".join([f"{o[0]}:{o[1]}" for o in entry.occurrences])

            context = ""
            if entry.comment[:12] == "translators:":
                context = entry.comment[13:]

            writer.writerow(
                [
                    "woocommerce",
                    occurrences,
                    context,
                    "",
                    entry.msgid,
                    entry.msgid_plural,
                ]
            )


if __name__ == "__main__":
    main(sys.argv[1:])
