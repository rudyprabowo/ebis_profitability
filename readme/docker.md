# DOCKER COMMAND

# Remove all container
docker rm -f $(docker ps -a -q)

# Stop all container
docker stop $(docker ps -a -q)

# Image list since {IMAGE_NAME}
docker images -a -f "since={IMAGE_NAME}"

# Remove all image since {IMAGE_NAME}
docker rmi -f $(docker images -a -f "since={IMAGE_NAME}" -q)

# Remove all image
docker rmi -f $(docker images -a -q)