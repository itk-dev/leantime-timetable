# Time table plugin

A plugin for registrering time in Leantime.

## Development

Clone this repository into your Leantime plugins folder:

``` shell
git clone https://github.com/ITK-Leantime/leantime-timetable.git app/Plugins/TimeTable
```

### Coding standards

``` shell
docker run --tty --interactive --rm --volume ${PWD}:/app itkdev/php8.3-fpm:latest composer install
docker run --tty --interactive --rm --volume ${PWD}:/app itkdev/php8.3-fpm:latest composer coding-standards-apply
docker run --tty --interactive --rm --volume ${PWD}:/app itkdev/php8.3-fpm:latest composer coding-standards-check
```

```shell
docker run --rm -v "$(pwd):/work" tmknom/prettier --write assets
docker run --rm -v "$(pwd):/work" tmknom/prettier --check assets
```

```shell
docker run --rm --volume $PWD:/md peterdavehello/markdownlint markdownlint --ignore vendor --ignore LICENSE.md '**/*.md' --fix
docker run --rm --volume $PWD:/md peterdavehello/markdownlint markdownlint --ignore vendor --ignore LICENSE.md '**/*.md'
```

### Code analysis

```shell
docker run --tty --interactive --rm --volume ${PWD}:/app itkdev/php8.3-fpm:latest composer install
docker run --tty --interactive --rm --volume ${PWD}:/app itkdev/php8.3-fpm:latest composer code-analysis
```
