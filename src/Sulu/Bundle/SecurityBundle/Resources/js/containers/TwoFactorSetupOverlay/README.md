The `TwoFactorSetupOverlay` is mounted once in the application and opens automatically when the `userStore` reports
that a two-factor setup is required for the current user. It has no props. A step based flow guides through the method
selection (skipped if only one method is configured), the setup with QR code and verification code, and optionally the
display of backup codes.

It is configured through static properties, set by the bundle on initialization:

| Static             | Description                                                              |
|--------------------|--------------------------------------------------------------------------|
| `endpoints`        | Map of the method name to the endpoint used to set it up and verify it.  |
| `methods`          | Names of the available two-factor methods.                               |
| `backupCodesEnabled` | Offers the backup codes after the setup if `true`.                     |

```jsx static
TwoFactorSetupOverlay.endpoints = {totp: '/admin/api/two-factor/totp'};
TwoFactorSetupOverlay.methods = ['totp'];
TwoFactorSetupOverlay.backupCodesEnabled = true;

<TwoFactorSetupOverlay />
```
