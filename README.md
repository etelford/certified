# Certified

## Generate *trusted* SSL certificates on macOS

You can easily generate a single domain certificate:

```
./ssl make mysite.local
```

Or, pass the `-w` option to generate a wildcard certificate:

```
./ssl make mysite.local -w
```

You'll be prompted for your user password **three** times: once by the command, and twice inside macOS system dialogs.

After you're finished you can open Keychain Access.app and view your certificate and see that it is marked as "Trusted".
