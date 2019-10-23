#!/bin/bash

vendor/bin/phpstan analyse -c phpstan.neon --level ${1:-0} .
