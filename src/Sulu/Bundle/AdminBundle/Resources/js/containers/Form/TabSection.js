// @flow
import React from 'react';
import {action, computed, observable, toJS} from 'mobx';
import {observer} from 'mobx-react';
import jexl from 'jexl';
import Divider from '../../components/Divider';
import Grid from '../../components/Grid';
import Tabs from '../../components/Tabs';
import conditionDataProviderRegistry from './registries/conditionDataProviderRegistry';
import FormInspector from './FormInspector';
import tabSectionStyles from './tabSection.scss';
import type {Element, Node} from 'react';
import type {ErrorCollection, Schema, SchemaEntry} from './types';

type Props = {|
    data: Object,
    errors?: ErrorCollection,
    formInspector: FormInspector,
    name: string,
    renderItem: (schemaField: SchemaEntry, schemaKey: string, schemaPath: string) => ?Node,
    schema: SchemaEntry,
    schemaPath: string,
    showAllErrors: boolean,
|};

type Tab = {|
    items: Schema,
    key: string,
    label: string,
    schemaPath: string,
|};

// every leaf of the nested errors is the error of a single field, blocks nest their errors in arrays
function countErrors(error: mixed): number {
    if (!error || typeof error !== 'object') {
        return 0;
    }

    if (Array.isArray(error)) {
        return error.reduce((count, item) => count + countErrors(item), 0);
    }

    if (typeof error.keyword === 'string') {
        return 1;
    }

    return Object.keys(error).reduce((count, key) => count + countErrors(error[key]), 0);
}

@observer
class TabSection extends React.Component<Props> {
    @observable selectedIndex: number = 0;

    @computed get conditionData() {
        const {data, formInspector} = this.props;

        return conditionDataProviderRegistry.getAll().reduce(
            function(data, conditionDataProvider) {
                return {...data, ...conditionDataProvider(data, undefined, formInspector)};
            },
            {...data}
        );
    }

    isVisible(schemaEntry: SchemaEntry): boolean {
        if (!schemaEntry.visibleCondition) {
            return true;
        }

        return jexl.evalSync(schemaEntry.visibleCondition, this.conditionData);
    }

    @computed get items(): Schema {
        return this.props.schema.items || {};
    }

    @computed get tabs(): Array<Tab> {
        const {schemaPath} = this.props;
        const items = this.items;

        return Object.keys(items)
            .filter((key) => items[key].type === 'section' && this.isVisible(items[key]))
            .map((key) => ({
                items: items[key].items || {},
                key,
                label: items[key].label || key,
                schemaPath: schemaPath + '/items/' + key,
            }));
    }

    @computed get activeIndex(): number {
        // a tab can disappear because of its visibleCondition, the first tab is selected instead
        return this.selectedIndex < this.tabs.length ? this.selectedIndex : 0;
    }

    countTabErrors(items: Schema): number {
        const errors = toJS(this.props.errors);

        if (!errors) {
            return 0;
        }

        return Object.keys(items).reduce((count, key) => {
            const item = items[key];

            if (item.type === 'section') {
                return count + this.countTabErrors(item.items || {});
            }

            return count + countErrors(errors[key]);
        }, 0);
    }

    // the fields of an unselected tab are not visible, therefore the tab itself has to show their errors
    renderBadges(tab: Tab): Array<Element<'span'>> {
        const {showAllErrors} = this.props;

        if (!showAllErrors) {
            return [];
        }

        const errorCount = this.countTabErrors(tab.items);

        if (!errorCount) {
            return [];
        }

        return [<span className={tabSectionStyles.errorBadge} key="errors">{errorCount}</span>];
    }

    renderItems(items: Schema, schemaPath: string): Array<?Node> {
        const {renderItem} = this.props;

        return Object.keys(items).map((key) => renderItem(items[key], key, schemaPath + '/items/' + key));
    }

    @action handleTabSelect = (index: number) => {
        this.selectedIndex = index;
    };

    render() {
        const {name, schema, schemaPath} = this.props;

        if (!this.isVisible(schema)) {
            return null;
        }

        const items = this.items;
        const tabs = this.tabs;
        const activeIndex = this.activeIndex;

        // properties next to the tabs belong to all tabs, therefore they are rendered above the tab bar
        const sharedItems: Schema = Object.keys(items)
            .filter((key) => items[key].type !== 'section')
            .reduce((sharedItems: Schema, key) => ({...sharedItems, [key]: items[key]}), {});

        const children = [];

        if (schema.label) {
            children.push(
                <Grid.Item colSpan={12} key="label">
                    <Divider>{schema.label}</Divider>
                </Grid.Item>
            );
        }

        children.push(...this.renderItems(sharedItems, schemaPath));

        if (tabs.length > 0) {
            children.push(
                <Grid.Item className={tabSectionStyles.tabBar} colSpan={12} key="tabs">
                    <Tabs
                        className={tabSectionStyles.tabs}
                        onSelect={this.handleTabSelect}
                        selectedIndex={activeIndex}
                        type="inline"
                    >
                        {tabs.map((tab) => (
                            <Tabs.Tab badges={this.renderBadges(tab)} key={tab.key}>{tab.label}</Tabs.Tab>
                        ))}
                    </Tabs>
                </Grid.Item>
            );
        }

        // all tabs stay rendered, so their fields keep their state and apply their default values like in a section
        tabs.forEach((tab, index) => {
            children.push(
                <Grid.Section
                    className={index !== activeIndex ? tabSectionStyles.hidden : undefined}
                    colSpan={12}
                    key={'tab-' + tab.key}
                >
                    {(this.renderItems(tab.items, tab.schemaPath): any)}
                </Grid.Section>
            );
        });

        return (
            <Grid.Section className={tabSectionStyles.tabSection} colSpan={schema.colSpan} key={name}>
                {(children: any)}
            </Grid.Section>
        );
    }
}

export default TabSection;
