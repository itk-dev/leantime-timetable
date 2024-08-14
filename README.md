# Time table plugin

A plugin for registrering time in Leantime.

## Development

Clone this repository into your Leantime plugins folder:

``` shell
git clone https://github.com/ITK-Leantime/leantime-timetable.git app/Plugins/TimeTable
```

### Coding standards

```shell
docker compose build
docker compose run --rm php npm install
docker compose run --rm php npm run coding-standards-apply
```

```shell
docker run --rm --volume "$(pwd):/md" peterdavehello/markdownlint markdownlint --ignore LICENSE.md --ignore vendor/ --ignore node_modules/ '**/*.md' --fix
docker run --rm --volume "$(pwd):/md" peterdavehello/markdownlint markdownlint --ignore LICENSE.md --ignore vendor/ --ignore node_modules/ '**/*.md'
```

```shell
docker run --rm --tty --volume "$(pwd):/app" peterdavehello/shellcheck shellcheck /app/bin/deploy
docker run --rm --tty --volume "$(pwd):/app" peterdavehello/shellcheck shellcheck /app/bin/create-release
```

```shell name=coding-standards-php
docker compose build
docker compose run --rm php composer install
docker compose run --rm php composer coding-standards-apply
docker compose run --rm php composer coding-standards-check
```

### Code analysis

```shell
docker run --tty --interactive --rm --volume ${PWD}:/app itkdev/php8.3-fpm:latest composer install
docker run --tty --interactive --rm --volume ${PWD}:/app itkdev/php8.3-fpm:latest composer code-analysis
```

## Test release build

``` shell
docker compose build && docker compose run --rm php bash bin/create-release dev-test
```

The create-release script replaces `@@VERSION@@` in
[register.php](https://github.com/ITK-Leantime/leantime-timetable/blob/develop/register.php#L13) and
[Services/TimeTable.php](https://github.com/ITK-Leantime/leantime-timetable/blob/develop/Services/TimeTable.php#L12)
with the tag provided (in the above it is `dev-test`).

## Deploy

The deploy script downloads a [release](https://github.com/ITK-Leantime/leantime-timetable/releases) from Github and
unzips it. The script should be passed a tag as argument. In the process the script deletes itself, but the script
finishes because it [is still in memory](https://linux.die.net/man/3/unlink).
