# schnitzler/frontend-user-login-token

> Token-based frontend login for TYPO3 — no password required, straight from the CLI.

Anyone managing TYPO3 instances with many frontend users across different groups knows the pain: to debug a specific user you either need their password or have to reset it temporarily. This extension solves that cleanly — a single CLI command generates a time-limited login link. No password, no database entry.

---

## Usage

The entry point is the following CLI command:

```bash
php vendor/bin/typo3 schnitzler:frontend-user-login-token:find-frontend-user [q]
```

Without an argument, all frontend users are listed. Passing a search term filters by the following fields:

- `uid`
- `username`
- `first_name`
- `last_name`

The result is a table of users with a login link that can be copied directly into the browser.

---

## Anatomy of a Login Link

```
/?logintype=login
  &login-token=eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9.eyJ1aWQiOjEsImV4cCI6MTc3ODE4MDAyMX0.b3Aw17vsaumpCehEpWmWht3mXEpMD-yxdGhxiN57X9M
  &hmac=2552db5124551ce5ff5377b70c966c1e1ab7d047
```

The link consists of three parts:

| Parameter | Purpose |
|---|---|
| `logintype=login` | Instructs TYPO3 to use the login mechanism |
| `login-token` | The JWT itself, containing `uid` and expiry timestamp |
| `hmac` | Signature to verify the token's integrity |

Tokens expire after **1 hour** by default.

---

## Technical Details

- The token is generated **on the fly** and is **never stored in the database**.
- It is a **JWT** (JSON Web Token) containing only the user ID and an expiration date.
- Once the extension detects a login link, it validates the signature and initiates the login — **without a POST request**.
- Both the JWT and the HMAC are generated using the **TYPO3 Security Framework**, derived from `$GLOBALS['TYPO3_CONF_VARS']['SYS']['encryptionKey']`.
- Tokens cannot be guessed due to cryptographic signing.

---

## Security Considerations

> [!WARNING]
> An intercepted login link grants immediate access to the corresponding frontend account — no further knowledge about the user required.

The extension is an excellent fit for **development and testing**. For production use, the risk should be consciously evaluated. That said, the author has been running it in production for over two years.
