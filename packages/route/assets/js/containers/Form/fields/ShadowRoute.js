// @flow
import React from 'react';
import {action, observable, reaction} from 'mobx';
import {observer} from 'mobx-react';
import {fieldRegistry, metadataStore} from 'sulu-admin-bundle/containers/Form';
import type {FieldTypeProps} from 'sulu-admin-bundle/types';
import type {Schema, SchemaEntry} from 'sulu-admin-bundle/containers/Form/types';

const ROUTE_FIELD_TYPES = ['route', 'page_tree_route'];

function findRouteSchemaEntry(schema: Schema): ?SchemaEntry {
    for (const key of Object.keys(schema)) {
        const entry = schema[key];

        if (entry.type === 'section' && entry.items) {
            const sectionEntry = findRouteSchemaEntry(entry.items);

            if (sectionEntry) {
                return sectionEntry;
            }
        } else if (ROUTE_FIELD_TYPES.includes(entry.type)) {
            return entry;
        }
    }
}

// Mirrors the route field of the current template, because the content tab is hidden while a shadow is enabled.
@observer
class ShadowRoute extends React.Component<FieldTypeProps<mixed>> {
    @observable routeSchemaEntry: ?SchemaEntry;
    templateDisposer: ?() => mixed;

    constructor(props: FieldTypeProps<mixed>) {
        super(props);

        const {formInspector} = this.props;

        this.templateDisposer = reaction(
            () => formInspector.getValueByPath('/template'),
            this.loadRouteSchemaEntry,
            {fireImmediately: true}
        );
    }

    componentWillUnmount() {
        if (this.templateDisposer) {
            this.templateDisposer();
        }
    }

    loadRouteSchemaEntry = (template: mixed) => {
        const {formInspector: {metadataOptions: {templateType} = {}}} = this.props;

        if (typeof templateType !== 'string') {
            return;
        }

        const templatePromise = typeof template === 'string'
            ? Promise.resolve(template)
            : metadataStore.getSchemaTypes(templateType).then((schemaTypes) => schemaTypes?.defaultType);

        templatePromise
            .then((type) => type ? metadataStore.getSchema(templateType, type) : {})
            .then(action((schema) => {
                this.routeSchemaEntry = findRouteSchemaEntry(schema);
            }));
    };

    render() {
        if (!this.routeSchemaEntry) {
            return null;
        }

        const {type, options: schemaOptions = {}} = this.routeSchemaEntry;
        const RouteFieldType = fieldRegistry.get(type);

        return (
            <RouteFieldType
                {...this.props}
                fieldTypeOptions={fieldRegistry.getOptions(type)}
                schemaOptions={schemaOptions}
            />
        );
    }
}

export default ShadowRoute;
