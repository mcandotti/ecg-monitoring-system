# Makefile for ECG monitoring system administration
# Author: IR1 Student Project

# Variables
ifeq ($(OS),Windows_NT)
	DOCKER_COMPOSE = docker-compose
else
	DOCKER_COMPOSE = docker compose
endif
DOCKER = docker

# Main commands
.PHONY: up down restart build logs clean setup backup restore shell help

# Help/documentation
help:
	@echo "Makefile for ECG monitoring system administration"
	@echo ""
	@echo "Usage:"
	@echo "  make <command>"
	@echo ""
	@echo "Commands:"
	@echo "  up              - Start containers"
	@echo "  down            - Stop containers" 
	@echo "  restart         - Restart containers"
	@echo "  build           - Rebuild Docker images"
	@echo "  logs            - Display container logs"
	@echo "  clean           - Clean project completely (containers, volumes, images)"
	@echo "  shell-web       - Open shell in web container"
	@echo "  shell-db        - Open shell in MySQL container"
	@echo "  status          - Show container status"
	@echo "  prune           - Remove unused containers and volumes"
	@echo "  help            - Show this help"

# Start containers
up:
	@echo "Starting containers..."
	$(DOCKER_COMPOSE) up -d
	@echo "Containers started."
	@echo "Web: http://localhost:80"
	@echo "PHPMyAdmin: http://localhost:8080"

# Stop containers
down:
	@echo "Stopping containers..."
	$(DOCKER_COMPOSE) down
	@echo "Containers stopped."

# Restart containers
restart:
	@echo "Restarting containers..."
	$(DOCKER_COMPOSE) restart
	@echo "Containers restarted."

# Rebuild Docker images
build:
	@echo "Building Docker images..."
	$(DOCKER_COMPOSE) build
	@echo "Images built."

# Display container logs
logs:
	$(DOCKER_COMPOSE) logs -f

# Clean project completely
clean:
	@echo "Cleaning project..."
	$(DOCKER_COMPOSE) down -v --rmi all
	@echo "Project cleaned."

# Open shell in web container
shell-web:
	@echo "Opening shell in web container..."
	$(DOCKER_COMPOSE) exec web bash

# Open shell in MySQL container
shell-db:
	@echo "Opening shell in MySQL container..."
	$(DOCKER_COMPOSE) exec mysql bash

# Show container status
status:
	@echo "Container status:"
	$(DOCKER_COMPOSE) ps

# Remove unused containers and volumes
prune:
	@echo "Removing unused containers and volumes..."
	$(DOCKER) system prune -f
	$(DOCKER) volume prune -f
	@echo "Cleanup complete."

# Install frontend dependencies (if needed)
frontend-deps:
	@echo "Installing frontend dependencies (to be implemented if needed)..."
	@echo "Dependencies installed."

# Default to showing help
default: help