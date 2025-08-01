#!/bin/bash

branch_name=$(git branch --show-current)
extracted_part=$(echo "$branch_name" | cut -d '-' -f 3-)
extracted_part_without_dashes=$(echo "$extracted_part" | tr '-' ' ')

gh pr create --base develop --head "$branch_name" --title "$extracted_part_without_dashes" --body "$extracted_part_without_dashes"
