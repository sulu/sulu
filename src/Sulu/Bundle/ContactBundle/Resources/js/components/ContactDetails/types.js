// @flow

export type Email = {|
    email: ?string,
    emailType: number,
|};

export type Fax = {|
    fax: ?string,
    faxType: number,
|};

export type Phone = {|
    phone: ?string,
    phoneType: number,
|};

export type SocialMedia = {|
    socialMediaType: number,
    username: ?string,
|};

export type Website = {|
    website: ?string,
    websiteType: number,
|};

export type ContactDetailsValue = {|
    emails: Array<Email>,
    faxes: Array<Fax>,
    phones: Array<Phone>,
    socialMedia: Array<SocialMedia>,
    websites: Array<Website>,
|};
