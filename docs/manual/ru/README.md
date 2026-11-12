# Руководство по системе управления учебным процессом (русский)

Для сотрудников, которые ежедневно администрируют систему, и для ИТ-специалистов, которые её устанавливают, развёртывают и обслуживают. Экраны доступны на русском языке (меню пользователя > язык).

Другие языки: [한국어](../ko/README.md), [English](../en/README.md), [简体中文](../zh_CN/README.md), [ລາວ](../lo/README.md), [Deutsch](../de/README.md), [Filipino](../fil/README.md), [Français](../fr/README.md), [日本語](../ja/README.md), [Монгол](../mn/README.md), [Bahasa Melayu](../ms/README.md), [नेपाली](../ne/README.md), [ไทย](../th/README.md), [Türkçe](../tr/README.md), [Oʻzbek](../uz/README.md), [Tiếng Việt](../vi/README.md). Исходная версия — корейская; остальные издания являются переводами, ожидающими проверки носителями языка.

## Руководство администратора

[admin/README.md](admin/README.md) — главы с 1 по 28: от начала работы, пользователей и ролей до настройки учебного заведения, приёма, записи на дисциплины, оценок, справок, выпуска и интеграции с LMS.

## Эксплуатация

| Файл | Содержание |
|---|---|
| [installation.md](operations/installation.md) | Требования, роли базы данных, конфигурация окружения, первая установка и обновления |
| [deployment-checklist.md](operations/deployment-checklist.md) | Версия PHP и усиление защиты php.ini, .env для эксплуатации, порядок развёртывания, плановые проверки |
| [commands.md](operations/commands.md) | Справочник по всем командам Artisan: параметры, записи аудита, коды завершения, примеры |
| [scheduled-jobs.md](operations/scheduled-jobs.md) | Постоянно работающие процессы, задания по расписанию и задания очереди, обнаружение сбоев |
| [backup-restore.md](operations/backup-restore.md) | Что копировать, примеры дампа и восстановления, учебные восстановления |
| [security-keys.md](operations/security-keys.md) | Три секретных ключа, их хранение, ротация ключа персональных данных |
| [audit-and-maintenance.md](operations/audit-and-maintenance.md) | Хеш-цепочка аудита, сроки хранения и очистка, очистка файлов, проверка состояния |
| [load-testing.md](operations/load-testing.md) | Нагрузочный тест k6 для пика записи на дисциплины и решение об Octane |
| [troubleshooting.md](operations/troubleshooting.md) | Типичные признаки неполадок, что проверить, трассировка отдельного запроса |
