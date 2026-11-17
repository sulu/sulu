This is a simple component rendering a preview of addresses for the [`CardCollection` view](#cardcollection), which is
used in the contact form. The `type` is shown together with the flags of the `primaryAddress`, `billingAddress` and
`deliveryAddress` props. Lines without a value are left out.

```javascript
<div>
    <AddressCardPreview
        billingAddress={true}
        city="Dornbirn"
        country="Austria"
        deliveryAddress={false}
        number="1"
        primaryAddress={true}
        state="Vorarlberg"
        street="Teststrasse"
        title="Headquarter"
        type="Business"
        zip="6850"
    />
    <br />
    <AddressCardPreview
        billingAddress={false}
        city={undefined}
        country={undefined}
        deliveryAddress={true}
        number={undefined}
        primaryAddress={false}
        state={undefined}
        street={undefined}
        title={undefined}
        type="Private"
        zip={undefined}
    />
</div>
```
