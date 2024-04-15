<p align="center"><a href="https://laravel.com" target="_blank"><img src="https://raw.githubusercontent.com/laravel/art/master/logo-lockup/5%20SVG/2%20CMYK/1%20Full%20Color/laravel-logolockup-cmyk-red.svg" width="400" alt="Laravel Logo"></a></p>

<p align="center">
<a href="https://github.com/laravel/framework/actions"><img src="https://github.com/laravel/framework/workflows/tests/badge.svg" alt="Build Status"></a>
<a href="https://packagist.org/packages/laravel/framework"><img src="https://img.shields.io/packagist/dt/laravel/framework" alt="Total Downloads"></a>
<a href="https://packagist.org/packages/laravel/framework"><img src="https://img.shields.io/packagist/v/laravel/framework" alt="Latest Stable Version"></a>
<a href="https://packagist.org/packages/laravel/framework"><img src="https://img.shields.io/packagist/l/laravel/framework" alt="License"></a>
</p>

# Neural_networks_bot

**Telegram Bot AI** — это телеграм-бот на Laravel, позволяющий пользователям взаимодействовать с различными нейросетями для выполнения специфических задач, таких как текстовая генерация, создание изображений, и преобразование текста в речь.

## Описание:

Telegram Bot AI Hub интегрирует передовые нейросети, включая GPT-4 Turbo, SDXL, DALL-E 3, Midjourney, и TTS-HD, предоставляя пользователю мощный инструмент для генерации контента и анализа данных прямо в телеграме.

## Функциональные возможности:

- **GPT-4 Turbo**: Генерация текста для ответов на запросы, статьи, поэзия и др.
- **SDXL**: Для генерации изображений от компании StabilityAI.
- **DALL-E 3**: Нейросеть для генерации изображений от OpenAI. Очень мощная модель для создания картинок. 
- **Midjourney**: Это самая мощная и популярная нейросеть для генерации картинок.
- **TTS-HD**: Высококачественное преобразование текста в речь.

## Технологический стек:

- **PHP 8.2**
- **Laravel 9.x**
- **MySQL**
- **Ngrok**
- **Telegram Bot API**
- **Telegram-bot-sdk**

## Установка

### Клонирование репозитория

```bash
git clone <URL_репозитория>
cd <название_клонированной_директории>
cp .env.example .env
# Редактируйте .env с вашими настройками базы данных
composer install
php artisan key:generate
php artisan migrate
php artisan serve

Так же понадобиться токен вашего бота.
И утилита типа ngrok, для пробрасывания вашего локального сервера на защищенный.
```