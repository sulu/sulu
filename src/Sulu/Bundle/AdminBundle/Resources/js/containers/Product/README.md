# Product containers

Product-specific admin UI for the `sulu/product-bundle` package.

This code lives in sulu core, not in the bundle, because sulu ships a prebuilt admin build for
tagged releases: a bundle carrying its own JavaScript would force every consuming project into a
custom admin build. `containers/AiApplication` lives here for the same reason.

> **NOTE:** These containers are experimental, can change at any time and are not covered by our
> BC promise.

The domain, the API and the entities live in `sulu/SuluProductBundle`. A change to the attributes
API needs a matching sulu release.

## Containers

- `ProductFamilyAttributes`: the family form's attribute list, field type `product_family_attributes`.
- `ProductAttributes`: a product's attribute values, field type `product_attributes`. Loads the
  `product_attributes` form metadata for the family in the form value (`productFamily`) or the parent
  product in the form options (`parentId`), with `variant=true` when the field declares that param,
  and renders it through `ProductAttributesRenderer` as one collapsible card per attribute group.
  The container owns a memory form store for that form (`memoryFormStoreFactory.createFromFormKey`,
  data keyed by field name `attribute_<id>`, the host value by `<id>`) and its own `FormInspector`
  for the rows. Validation is the host form's: its JSON schema carries the family's attribute rules
  (an `if/then` branch per family, like block types), a row finishes on the host under
  `/attributes/<id>`, and the errors come back through the field's `error` prop to the rows.
- `AttributeGroupTable`: the flat table both containers put inside a card.
