This is a simple component rendering a preview of bank accounts for the [`CardCollection` view](#cardcollection), which
is used in the contact form. The `bankName` is optional, `iban` and `bic` are required.

```javascript
<div>
    <BankCardPreview bankName="Example Bank" bic="COBADEFFXXX" iban="DE89370400440532013000" />
    <br />
    <BankCardPreview bic="COBADEFFXXX" iban="DE89370400440532013000" />
</div>
```
