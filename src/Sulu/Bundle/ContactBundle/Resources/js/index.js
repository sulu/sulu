// @flow
import React from 'react';
import {when} from 'mobx';
import {CardCollection, fieldRegistry} from 'sulu-admin-bundle/containers';
import {bundleReady, initializer} from 'sulu-admin-bundle/services';
import {translate} from 'sulu-admin-bundle/utils';
import AddressCardPreview from './components/AddressCardPreview';
import BankCardPreview from './components/BankCardPreview';
import Iban from './containers/Form/fields/Iban';
import Bic from './containers/Form/fields/Bic';

fieldRegistry.add('iban', Iban);
fieldRegistry.add('bic', Bic);

initializer.addUpdateConfigHook('sulu_contact', (config: Object, initialized: boolean) => {
    if (initialized) {
        return;
    }

    when(
        () => !!initializer.initializedTranslationsLocale, (): void => {
            fieldRegistry.add(
                'addresses',
                CardCollection,
                {
                    addOverlayTitle: 'sulu_contact.add_address',
                    editOverlayTitle: 'sulu_contact.edit_address',
                    renderCardContent: function AddressCard(card) {
                        const addressType = config.addressTypes
                            .find((addressType) => card.addressType === addressType.id);

                        const country = config.countries
                            .find((country) => card.country === country.id);

                        return (
                            <AddressCardPreview
                                billingAddress={card.billingAddress}
                                city={card.city}
                                country={country ? country.name : undefined}
                                deliveryAddress={card.deliveryAddress}
                                number={card.number}
                                primaryAddress={card.primaryAddress}
                                state={card.state}
                                street={card.street}
                                title={card.title}
                                type={translate(addressType.name)}
                                zip={card.zip}
                            />
                        );
                    },
                    schema: {
                        title: {
                            label: translate('sulu_admin.title'),
                            type: 'text_line',
                        },
                        addresTypeInformation: {
                            items: {
                                addressType: {
                                    options: {
                                        default_value: {
                                            value: config.addressTypes[0].id,
                                        },
                                        values: {
                                            value: config.addressTypes.map((addressType) => ({
                                                name: addressType.id,
                                                title: translate(addressType.name),
                                            })),
                                        },
                                    },
                                    size: 6,
                                    type: 'single_select',
                                },
                                primaryAddress: {
                                    options: {
                                        label: {
                                            title: translate('sulu_contact.primary_address'),
                                        },
                                    },
                                    size: 6,
                                    type: 'checkbox',
                                },
                                deliveryAddress: {
                                    options: {
                                        label: {
                                            title: translate('sulu_contact.delivery_address'),
                                        },
                                    },
                                    size: 6,
                                    type: 'checkbox',
                                },
                                billingAddress: {
                                    options: {
                                        label: {
                                            title: translate('sulu_contact.billing_address'),
                                        },
                                    },
                                    size: 6,
                                    type: 'checkbox',
                                },
                            },
                            type: 'section',
                        },
                        address: {
                            items: {
                                street: {
                                    label: translate('sulu_contact.street'),
                                    size: 8,
                                    type: 'text_line',
                                },
                                number: {
                                    label: translate('sulu_contact.number'),
                                    size: 4,
                                    type: 'text_line',
                                },
                                addition: {
                                    label: translate('sulu_contact.address_line'),
                                    type: 'text_line',
                                },
                                zip: {
                                    label: translate('sulu_contact.zip'),
                                    size: 4,
                                    type: 'text_line',
                                },
                                city: {
                                    label: translate('sulu_contact.city'),
                                    size: 8,
                                    type: 'text_line',
                                },
                                state: {
                                    label: translate('sulu_contact.state'),
                                    type: 'text_line',
                                },
                                country: {
                                    label: translate('sulu_contact.country'),
                                    options: {
                                        values: {
                                            value: config.countries.map((country) => ({
                                                name: country.id,
                                                title: country.name,
                                            })),
                                        },
                                    },
                                    type: 'single_select',
                                },
                            },
                            type: 'section',
                        },
                        postbox: {
                            items: {
                                postboxNumber: {
                                    label: translate('sulu_contact.postbox_number'),
                                    type: 'text_line',
                                },
                                postboxPostcode: {
                                    label: translate('sulu_contact.postbox_zip'),
                                    size: 4,
                                    type: 'text_line',
                                },
                                postboxCity: {
                                    label: translate('sulu_contact.postbox_city'),
                                    size: 8,
                                    type: 'text_line',
                                },
                            },
                            type: 'section',
                        },
                        note: {
                            items: {
                                note: {
                                    label: translate('sulu_contact.note'),
                                    type: 'text_area',
                                },
                            },
                            type: 'section',
                        },
                    },
                }
            );

            fieldRegistry.add(
                'bankAccounts',
                CardCollection,
                {
                    addOverlayTitle: 'sulu_contact.add_bank_account',
                    editOverlayTitle: 'sulu_contact.edit_bank_account',
                    jsonSchema: {
                        required: ['iban'],
                    },
                    renderCardContent: function BankCard(card) {
                        return (
                            <BankCardPreview
                                bankName={card.bankName}
                                bic={card.bic}
                                iban={card.iban}
                            />
                        );
                    },
                    schema: {
                        bankName: {
                            label: translate('sulu_contact.bank'),
                            type: 'text_line',
                        },
                        iban: {
                            label: translate('sulu_contact.iban'),
                            required: true,
                            size: 8,
                            type: 'iban',
                        },
                        bic: {
                            label: translate('sulu_contact.bic'),
                            size: 4,
                            type: 'bic',
                        },
                    },
                }
            );
        }
    );
});

bundleReady();
