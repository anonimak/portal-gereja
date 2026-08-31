SHELL := /bin/sh
COMPOSE := ./scripts/compose.sh

.PHONY: help env build up down logs status shell artisan migrate seed backup restore

help:
	@echo "── Portal Gereja — Docker/Podman workflow ──"
	@echo "make env      siapkan .env + generate secret (tanpa PHP di host)"
	@echo "make up       build image & jalankan semua service (db, app, queue, scheduler, web)"
	@echo "make down     hentikan service (volume data tetap tersimpan)"
	@echo "make logs     ikuti log semua service"
	@echo "make status   daftar container + status health"
	@echo "make shell    masuk ke shell container app"
	@echo "make artisan c='route:list'   jalankan perintah artisan"
	@echo "make migrate  jalankan migrasi database"
	@echo "make seed     isi data demo + super admin (untuk review)"
	@echo "make backup   dump database ke backups/"
	@echo "make restore f=backups/xxx.sql   restore database"

env:
	./scripts/setup-env.sh

build:
	$(COMPOSE) build

up:
	$(COMPOSE) up -d --build

down:
	$(COMPOSE) down

logs:
	$(COMPOSE) logs -f

status:
	$(COMPOSE) ps

shell:
	$(COMPOSE) exec app sh

artisan:
	$(COMPOSE) exec app php artisan $(c)

migrate:
	$(COMPOSE) exec app php artisan migrate --force

seed:
	$(COMPOSE) exec app php artisan db:seed --force

backup:
	@mkdir -p backups
	@$(COMPOSE) exec -T db sh -c 'mariadb-dump --single-transaction -u"$$MARIADB_USER" -p"$$MARIADB_PASSWORD" "$$MARIADB_DATABASE"' > backups/portal-$$(date +%Y%m%d-%H%M%S).sql
	@echo "Backup tersimpan di backups/"

restore:
	@test -n "$(f)" || (echo "Pemakaian: make restore f=backups/xxx.sql"; exit 1)
	@$(COMPOSE) exec -T db sh -c 'exec mariadb -u"$$MARIADB_USER" -p"$$MARIADB_PASSWORD" "$$MARIADB_DATABASE"' < $(f)
	@echo "Restore selesai."
