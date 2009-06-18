/*
 *  SSHTools - Java SSH2 API
 *
 *  Copyright (C) 2002 Lee David Painter.
 *
 *  This program is free software; you can redistribute it and/or
 *  modify it under the terms of the GNU Library General Public License
 *  as published by the Free Software Foundation; either version 2 of
 *  the License, or (at your option) any later version.
 *
 *  You may also distribute it and/or modify it under the terms of the
 *  Apache style J2SSH Software License. A copy of which should have
 *  been provided with the distribution.
 *
 *  This program is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  License document supplied with your distribution for more details.
 *
 */

package com.sshtools.j2ssh.transport.publickey;

import java.io.File;
import java.io.FileInputStream;
import java.io.IOException;
import java.util.Iterator;

/**
 *
 *
 * @author $author$
 * @version $Revision: 1.21 $
 */
public class SshPrivateKeyFile {
  private SshPrivateKeyFormat format;
  private byte[] keyblob;

  /**
   * Creates a new SshPrivateKeyFile object.
   *
   * @param keyblob
   * @param format
   */
  protected SshPrivateKeyFile(byte[] keyblob, SshPrivateKeyFormat format) {
    this.keyblob = keyblob;
    this.format = format;
  }

  /**
   *
   *
   * @return
   */
  public byte[] getBytes() {
    return keyblob;
  }

  /**
   *
   *
   * @param oldPassphrase
   * @param newPassphrase
   *
   * @throws InvalidSshKeyException
   */
  public void changePassphrase(String oldPassphrase, String newPassphrase) throws
      InvalidSshKeyException {
    byte[] raw = format.decryptKeyblob(keyblob, oldPassphrase);
    keyblob = format.encryptKeyblob(raw, newPassphrase);
  }

  /**
   *
   *
   * @param formattedKey
   *
   * @return
   *
   * @throws InvalidSshKeyException
   */
  public static SshPrivateKeyFile parse(byte[] formattedKey) throws
      InvalidSshKeyException {
    if (formattedKey == null) {
      throw new InvalidSshKeyException("Key data is null");
    }


    // Try the default private key format
    SshPrivateKeyFormat format;

    format = SshPrivateKeyFormatFactory.newInstance(SshPrivateKeyFormatFactory
        .getDefaultFormatType());

    boolean valid = format.isFormatted(formattedKey);

    if (!valid) {

      Iterator<String> it = SshPrivateKeyFormatFactory.getSupportedFormats()
          .iterator();
      String ft;

      while (it.hasNext() && !valid) {
        ft = it.next();
        format = SshPrivateKeyFormatFactory.newInstance(ft);
        valid = format.isFormatted(formattedKey);
      }
    }

    if (valid) {
      return new SshPrivateKeyFile(formattedKey, format);
    }
    else {
      throw new InvalidSshKeyException(
          "The key format is not a supported format");
    }
  }

  /**
   *
   *
   * @param keyfile
   *
   * @return
   *
   * @throws InvalidSshKeyException
   * @throws IOException
   */
  public static SshPrivateKeyFile parse(File keyfile) throws
      InvalidSshKeyException, IOException {
    FileInputStream in = new FileInputStream(keyfile);
    byte[] data = null;

    try {
      data = new byte[in.available()];
      in.read(data);
    }
    finally {
      try {
          in.close();
      }
      catch (IOException ex) {
      }
    }

    return parse(data);
  }

  /**
   *
   *
   * @return
   */
  public boolean isPassphraseProtected() {
    return format.isPassphraseProtected(keyblob);
  }

  /*public void changePassphrase(String oldPassphrase, String newPassphrase)
   throws InvalidSshKeyException {
   keyblob = format.changePassphrase(keyblob, oldPassphrase, newPassphrase);
    }*/
  public static SshPrivateKeyFile create(SshPrivateKey key,
                                         String passphrase,
                                         SshPrivateKeyFormat format) throws
      InvalidSshKeyException {
    byte[] keyblob = format.encryptKeyblob(key.getEncoded(), passphrase);

    return new SshPrivateKeyFile(keyblob, format);
  }

  /**
   *
   *
   * @return
   */
  public SshPrivateKeyFormat getFormat() {
    return format;
  }
  
  /**
   *
   *
   * @return
   */
  @Override
public String toString() {
    return new String(keyblob);
  }

}
