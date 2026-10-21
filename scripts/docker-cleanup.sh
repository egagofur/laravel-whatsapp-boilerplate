#!/usr/bin/env sh
set -eu

echo "Cleaning stopped containers..."
docker container prune -f

echo "Cleaning unused networks..."
docker network prune -f

echo "Cleaning dangling images..."
docker image prune -f

echo "Cleaning unused images older than 24 hours..."
docker image prune -a -f --filter "until=24h"

echo "Cleaning build cache older than 24 hours..."
docker builder prune -f --filter "until=24h"

echo "Docker cleanup finished. Volumes were not pruned."
