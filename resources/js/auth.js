class Auth {
  constructor() {}

  async register() {
    const createOptions = await this.getCreateOptions();

    const publicKeyCredentialCreationOptions = {
      challenge: this.base64URLStringToBuffer(createOptions.challenge),
      rp: {
        id: createOptions.rp.id,
        name: createOptions.rp.name,
      },
      user: {
        id: new TextEncoder().encode(window.atob(createOptions.user.id)),
        name: createOptions.user.name,
        displayName: createOptions.user.displayName,
      },
      pubKeyCredParams: createOptions.pubKeyCredParams,
      excludeCredentials: [],
      authenticatorSelection: createOptions.authenticatorSelection,
      timeout: 60000,
    };

    const credential = await navigator.credentials.create({
      publicKey: publicKeyCredentialCreationOptions
    });
    if (!credential) {
      throw new Error('Error generating a passkey');
    }

    const authenticatorAttestationResponse = {
      id: credential.id ? credential.id : null,
      type: credential.type ? credential.type : null,
      rawId: credential.rawId ? this.bufferToBase64URLString(credential.rawId) : null,
      response: {
        attestationObject: credential.response.attestationObject ? this.bufferToBase64URLString(credential.response.attestationObject) : null,
        clientDataJSON: credential.response.clientDataJSON ? this.bufferToBase64URLString(credential.response.clientDataJSON) : null,
      }
    };

    const registerCredential = await window.fetch('/admin/passkeys/register', {
      method: 'POST',
      body: JSON.stringify(authenticatorAttestationResponse),
      cache: 'no-cache',
      headers: {
        'Content-Type': 'application/json',
        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
      },
    });

    if (!registerCredential.ok) {
      throw new Error('Error saving the passkey');
    }

    window.location.reload();
  }

  async getCreateOptions() {
    const response = await fetch('/admin/passkeys/register', {
      method: 'GET',
    });

    return await response.json();
  }

  async login() {
    const loginData = await this.getLoginData();

    const publicKeyCredential = await navigator.credentials.get({
      publicKey: {
        challenge: this.base64URLStringToBuffer(loginData.challenge),
        userVerification: loginData.userVerification,
        timeout: 60000,
      }
    });

    if (!publicKeyCredential) {
      throw new Error('Authentication failed');
    }

    const authenticatorAttestationResponse = {
      id: publicKeyCredential.id ? publicKeyCredential.id : '',
      type: publicKeyCredential.type ? publicKeyCredential.type : '',
      rawId: publicKeyCredential.rawId ? this.bufferToBase64URLString(publicKeyCredential.rawId) : '',
      response: {
        authenticatorData: publicKeyCredential.response.authenticatorData ? this.bufferToBase64URLString(publicKeyCredential.response.authenticatorData) : '',
        clientDataJSON: publicKeyCredential.response.clientDataJSON ? this.bufferToBase64URLString(publicKeyCredential.response.clientDataJSON) : '',
        signature: publicKeyCredential.response.signature ? this.bufferToBase64URLString(publicKeyCredential.response.signature) : '',
        userHandle: publicKeyCredential.response.userHandle ? this.bufferToBase64URLString(publicKeyCredential.response.userHandle) : '',
      },
    };

    const loginAttempt = await window.fetch('/login/passkey', {
      method: 'POST',
      body: JSON.stringify(authenticatorAttestationResponse),
      headers: {
        'Content-Type': 'application/json',
        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
      },
    });

    if (!loginAttempt.ok) {
      throw new Error('Login failed');
    }

    window.location.assign('/admin');
  }

  async getLoginData() {
    const response = await fetch('/login/passkey', {
      method: 'GET',
    });

    return await response.json();
  }

  /**
   * Convert a base64 URL string to a buffer.
   *
   * Sourced from https://github.com/MasterKale/SimpleWebAuthn/blob/master/packages/browser/src/helpers/base64URLStringToBuffer.ts#L8
   *
   * @param {string} base64URLString
   * @returns {ArrayBuffer}
   */
  base64URLStringToBuffer(base64URLString) {
    // Convert from Base64URL to Base64
    const base64 = base64URLString.replace(/-/g, '+').replace(/_/g, '/');
    /**
     * Pad with '=' until it's a multiple of four
     * (4 - (85 % 4 = 1) = 3) % 4 = 3 padding
     * (4 - (86 % 4 = 2) = 2) % 4 = 2 padding
     * (4 - (87 % 4 = 3) = 1) % 4 = 1 padding
     * (4 - (88 % 4 = 0) = 4) % 4 = 0 padding
     */
    const padLength = (4 - (base64.length % 4)) % 4;
    const padded = base64.padEnd(base64.length + padLength, '=');
    // Convert to a binary string
    const binary = window.atob(padded);
    // Convert binary string to buffer
    const buffer = new ArrayBuffer(binary.length);
    const bytes = new Uint8Array(buffer);
    for (let i = 0; i < binary.length; i++) {
      bytes[i] = binary.charCodeAt(i);
    }
    return buffer;
  }

  /**
   * Convert a buffer to a base64 URL string.
   *
   * Sourced from https://github.com/MasterKale/SimpleWebAuthn/blob/master/packages/browser/src/helpers/bufferToBase64URLString.ts#L7
   *
   * @param {ArrayBuffer} buffer
   * @returns {string}
   */
  bufferToBase64URLString(buffer) {
    const bytes = new Uint8Array(buffer);
    let str = '';
    for (const charCode of bytes) {
      str += String.fromCharCode(charCode);
    }
    const base64String = btoa(str);
    return base64String.replace(/\+/g, '-').replace(/\//g, '_').replace(/=/g, '');
  }
}

export { Auth };
