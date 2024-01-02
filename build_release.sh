#!/bin/bash

file=omniva-woocommerce.zip
[ -f $file ] && rm $file
git archive HEAD -o $file
