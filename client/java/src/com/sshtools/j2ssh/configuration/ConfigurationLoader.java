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

package com.sshtools.j2ssh.configuration;

import java.io.File;
import java.io.FileInputStream;
import java.io.FileNotFoundException;
import java.io.FilenameFilter;
import java.io.InputStream;
import java.security.AccessControlException;
import java.security.AccessController;
import java.security.SecureRandom;
import java.util.Properties;
import java.util.PropertyPermission;
import java.util.Vector;

import com.sshtools.j2ssh.transport.cipher.SshCipherFactory;
import com.sshtools.j2ssh.transport.compression.SshCompressionFactory;
import com.sshtools.j2ssh.transport.hmac.SshHmacFactory;
import com.sshtools.j2ssh.transport.kex.SshKeyExchangeFactory;
import com.sshtools.j2ssh.transport.publickey.SshKeyPairFactory;
import com.sshtools.j2ssh.transport.publickey.SshPrivateKeyFormatFactory;
import com.sshtools.j2ssh.transport.publickey.SshPublicKeyFormatFactory;
import com.sshtools.j2ssh.util.ExtensionClassLoader;

/**
 *
 *
 * @author $author$
 * @version $Revision: 1.68 $
 */
public class ConfigurationLoader {
  private static Vector<ConfigurationContext> contexts = new Vector<ConfigurationContext>();
  private static SecureRandom rnd;
  private static ExtensionClassLoader ext = null;
  private static ClassLoader clsLoader = null;
  private static String homedir;
  private static boolean initialized = false;
  private static Object initializationLock = new Object();

  static {
    // Get the sshtools.home system property. If system properties can not be
    // read then we are running in a sandbox
    //     try {
    homedir = checkAndGetProperty("sshtools.home",
                                  System.getProperty("java.home"));

    if ( (homedir != null) && !homedir.endsWith(File.separator)) {
      homedir += File.separator;
    }

    rnd = new SecureRandom();
    rnd.nextInt();
  }

  /**
   *
   *
   * @return
   */
  public static SecureRandom getRND() {
    return rnd;
  }

  /**
   *
   *
   * @param projectname
   * @param versionFile
   *
   * @return
   */
  public static String getVersionString(String projectname, String versionFile) {
    Properties properties = new Properties();
    String version = projectname;

    try {
      properties.load(loadFile(versionFile));

      String project = projectname.toLowerCase();
      String major = properties.getProperty(project + ".version.major");
      String minor = properties.getProperty(project + ".version.minor");
      String build = properties.getProperty(project + ".version.build");
      String type = properties.getProperty(project + ".project.type");

      if ( (major != null) && (minor != null) && (build != null)) {
        version += (" " + major + "." + minor + "." + build);
      }

      if (type != null) {
        version += (" " + type);
      }
    }
    catch (Exception e) {
    }

    return version;
  }

  /**
   *
   *
   * @param property
   * @param defaultValue
   *
   * @return
   */
  public static String checkAndGetProperty(String property,
                                           String defaultValue) {
    //  Check for access to sshtools.platform
    try {
      if (System.getSecurityManager() != null) {
        AccessController.checkPermission(new PropertyPermission(
            property, "read"));
      }

      return System.getProperty(property, defaultValue);
    }
    catch (AccessControlException ace) {
      return defaultValue;
    }
  }

  /**
   *
   *
   * @param force
   *
   * @throws ConfigurationException
   */
  public static void initialize(boolean force) throws ConfigurationException {
    initialize(force, new DefaultConfigurationContext());
  }

  /**
   * <p>
   * Initializes the J2SSH api with a specified configuration context. This
   * method will attempt to load the Bouncycastle JCE if it detects the java
   * version is 1.3.1.
   * </p>
   *
   * @param force force the configuration to load even if a configuration
   *        already exists
   * @param context the configuration context to load
   *
   * @throws ConfigurationException if the configuration is invalid or if a
   *         security provider is not available
   */
  public static void initialize(boolean force, ConfigurationContext context) throws
      ConfigurationException {

    synchronized (initializationLock) {
      if (initialized && !force) {
        return;
      }

      if (ext == null) {

        // We need to setup the dynamic class loading with the extension jars
        ext = new ExtensionClassLoader(ConfigurationLoader.class
                                       .getClassLoader());

        try {
          //  Jar files to add to the classpath
          File dir = new File(homedir + "lib" + File.separator
                              + "ext");

          // Filter for .jar files
          FilenameFilter filter = new FilenameFilter() {
            public boolean accept(File dir, String name) {
              return name.endsWith(".jar");
            }
          };

          // Get the list
          File[] children = dir.listFiles(filter);
          //List<File> classpath = new Vector<File>();

          if (children != null) {
            for (int i = 0; i < children.length; i++) {
              // Get filename of file or directory
              /*log.info("Extension "
                       + children[i].getAbsolutePath()
                       + " being added to classpath");*/
              ext.add(children[i]);
            }
          }

        }
        catch (AccessControlException ex) {
          //log.info(
            //  "Cannot access lib/ext directory, extension classes will not be loaded");
        }
      }

      SshCipherFactory.initialize();
      SshPrivateKeyFormatFactory.initialize();
      SshPublicKeyFormatFactory.initialize();
      SshCompressionFactory.initialize();
      SshHmacFactory.initialize();
      SshKeyPairFactory.initialize();
      SshKeyExchangeFactory.initialize();

      context.initialize();
      contexts.add(context);

      initialized = true;
    }
  }

  /**
   *
   *
   * @return
   */
  public static String getConfigurationDirectory() {
    return homedir + "conf/";
  }

  /**
   *
   *
   * @param name
   *
   * @return
   *
   * @throws ClassNotFoundException
   * @throws ConfigurationException
   */
  public static Class<?> getExtensionClass(String name) throws
      ClassNotFoundException, ConfigurationException {
    if (!initialized) {
      initialize(false);
    }

    if (ext == null) {
      throw new ClassNotFoundException("Configuration not initialized");
    }

    return ext.loadClass(name);
  }

  public static ExtensionClassLoader getExtensionClassLoader() {
    return ext;
  }
  
  /**
   *
   *
   * @return
   */
  public static ClassLoader getContextClassLoader() {
    return ConfigurationLoader.clsLoader;
  }

  /**
   *
   *
   * @return
   */
  public static boolean isContextClassLoader() {
    return (clsLoader != null);
  }

  /**
   *
   *
   * @param filename
   *
   * @return
   *
   * @throws FileNotFoundException
   */
  public static InputStream loadFile(String filename) throws
      FileNotFoundException {
    FileInputStream in;

    try {
      in = new FileInputStream(getConfigurationDirectory() + filename);

      return in;
    }
    catch (FileNotFoundException fnfe) {
    }

    try {
      in = new FileInputStream(homedir + filename);

      return in;
    }
    catch (FileNotFoundException fnfe) {
    }

    in = new FileInputStream(filename);

    return in;
  }

  static class DefaultConfigurationContext
      implements ConfigurationContext {
    public void initialize() throws ConfigurationException {
      // Do nothing
    }

    public boolean isConfigurationAvailable(Class<?> cls) {
      return false;
    }

    public Object getConfiguration(Class<?> cls) throws ConfigurationException {
      throw new ConfigurationException(
          "Default configuration does not contain " + cls.getName());
    }
  }
}
