The `TwoFactorSetupOverlay` is mounted once in the application and opens automatically when the `userStore` reports
that a two-factor setup is required for the current user. It has no props. A step based flow guides through the method
selection (skipped if only one method is configured), the setup with QR code and verification code, and optionally the
display of backup codes.

It is configured through static properties, set by the bundle on initialization:

| Static             | Description                                                              |
|--------------------|--------------------------------------------------------------------------|
| `endpoints`        | Map of the operation (`twoFactorSetup`, `twoFactorConfirm`, `twoFactorBackupCodes`) to its endpoint. The chosen method is sent in the request body. |
| `methods`          | Names of the available two-factor methods.                               |
| `backupCodesEnabled` | Offers the backup codes after the setup if `true`.                     |

```jsx static
// the bundle sets `endpoints` from the `sulu_security` config; the URLs below are placeholders
TwoFactorSetupOverlay.endpoints = {
    twoFactorSetup: '/admin/api/two-factor/setup',
    twoFactorConfirm: '/admin/api/two-factor/confirm',
    twoFactorBackupCodes: '/admin/api/two-factor/backup-codes',
};
TwoFactorSetupOverlay.methods = ['totp'];
TwoFactorSetupOverlay.backupCodesEnabled = true;

<TwoFactorSetupOverlay />
```
