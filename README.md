**Hypertube allows you to reference and use modules or packages written in (Java/Kotlin/Groovy/Clojure, C#/VB.NET, Ruby, Perl, Python, PHP, JavaScript/TypeScript) like they were created in your technology.**

It works on Linux/Windows and MacOS for applications created in JVM, CLR/Netcore, Perl, Python, PHP, Ruby, NodeJS, C++ or GoLang and gives you unparalleled freedom and flexibility with native performance in building your mixed-technologies products.
Let it be accessing best AI or cryptography libraries, devices SDKs, legacy client modules, internal custom packages or anything from public repositories available on NPM, Nuget, PyPI, Maven/Gradle, RubyGems or GitHub.
Get free from programming languages barriers today! For more information check out our guides at https://www.hypertube.dev/guides/v2/

**To automatically unpack hypertube php sdk to vendor add the following entry in composer.json:**

```composer.json
"scripts": {
    "post-install-cmd": [
        "@post-update-cmd"
    ],
    "post-update-cmd": [
        "@php vendor/graftcode/hypertube-php-sdk/scripts/download-lfs-files.php",
        "@php vendor/graftcode/hypertube-php-sdk/scripts/extract-zip-packages.php"
    ]
}
```
