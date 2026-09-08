.DEFAULT_GOAL := help
.PHONY: help up down migrate fresh test lint shell logs restore-test

help: ## Lista los objetivos disponibles
	@grep -E '^[a-zA-Z_-]+:.*?## .*$$' $(MAKEFILE_LIST) | \
		awk 'BEGIN {FS = ":.*?## "}; {printf "  \033[36m%-14s\033[0m %s\n", $$1, $$2}'

up: ## Levanta el entorno de desarrollo (app, postgres, minio, worker)
	docker compose up -d --build

down: ## Para y elimina los contenedores (conserva los volúmenes)
	docker compose down

migrate: ## Ejecuta las migraciones pendientes
	docker compose exec app php artisan migrate

fresh: ## Recrea la base de datos desde cero (borra los datos)
	docker compose exec app php artisan migrate:fresh --seed

test: ## Ejecuta la suite de tests dentro del contenedor
	docker compose exec app php artisan test

lint: ## Comprueba el estilo de código con Pint (sin modificar ficheros)
	docker compose exec app vendor/bin/pint --test

shell: ## Abre una shell dentro del contenedor de la aplicación
	docker compose exec app sh

logs: ## Sigue los logs de todos los servicios
	docker compose logs -f

restore-test: ## Ensayo cronometrado: crea un volcado y lo restaura contra una BD limpia
	@echo "== Ensayo de restauración =="
	@START=$$(date +%s); \
	docker compose exec -T postgres pg_dump -U casa47bis -d casa47bis --format=custom --file=/tmp/restore-test.dump; \
	docker compose exec -T postgres dropdb -U casa47bis --if-exists casa47bis_restore_test; \
	docker compose exec -T postgres createdb -U casa47bis casa47bis_restore_test; \
	docker compose exec -T postgres pg_restore -U casa47bis -d casa47bis_restore_test /tmp/restore-test.dump; \
	docker compose exec -T postgres dropdb -U casa47bis casa47bis_restore_test; \
	docker compose exec -T postgres rm -f /tmp/restore-test.dump; \
	END=$$(date +%s); \
	echo "== Restauración completada en $$((END - START))s =="
