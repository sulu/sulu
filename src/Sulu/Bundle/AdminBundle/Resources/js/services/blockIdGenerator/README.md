The `blockIdGenerator` service generates unique block IDs from the backend API. These IDs are used to identify individual blocks in block-based content structures.

```javascript static
import blockIdGenerator from '../../services/blockIdGenerator';

// Generate a new unique block ID
blockIdGenerator.generateBlockId()
    .then((id) => {
        console.log('Generated block ID:', id);
        // Example output: "01HZV4B6QZXY..."
    });
```

The service is typically used by containers (not components) that manage block collections, such as `FieldBlocks`.
