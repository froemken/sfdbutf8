# TYPO3 Extension `sfdbutf8`

![Build Status](https://github.com/froemken/sfdbutf8/workflows/CI/badge.svg)

With sfdbutf8 you can change the collation of tables and columns of
TYPO3s default database connection.

It can only change the collation from utf8_* to a different or same
utf8_* collation. It's not possible to change from latin* to utf8_*.

Keep in mind that no conversion of the content itself will happen!

## 2 Usage

### 2.1 Installation

#### Installation using Composer

The recommended way to install the extension is using Composer.

Run the following command within your Composer based TYPO3 project:

```
composer require stefanfroemken/sfdbutf8
```

#### Installation as extension from TYPO3 Extension Repository (TER)

Download and install `sfdbutf8` with the extension manager module.

### 2.2 Minimal setup

1) Visit BE module of sfdbutf8
2) Analyze tables collations
3) Choose a collation you want to use
4) Click "convert"
