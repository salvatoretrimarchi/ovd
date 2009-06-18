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

package com.sshtools.j2ssh.connection;


/**
 *
 *
 * @author $author$
 * @version $Revision: 1.19 $
 */
public class ChannelDataWindow {
  long windowSpace = 0;

  /**
   * Creates a new ChannelDataWindow object.
   */
  public ChannelDataWindow() {
  }

  /**
   *
   *
   * @return
   */
  public synchronized long getWindowSpace() {
    return windowSpace;
  }

  /**
   *
   *
   * @param count
   *
   * @return
   */
  public final synchronized long consumeWindowSpace(int count) {
    if (windowSpace < count) {
      waitForWindowSpace(count);
    }

    windowSpace -= count;

    return windowSpace;
  }

  /**
   *
   *
   * @param count
   */
  public synchronized void increaseWindowSpace(long count) {
    /*if (log.isDebugEnabled()) {
      log.debug("Increasing window space by " + String.valueOf(count));
    }*/

    windowSpace += count;

    notifyAll();
  }

  /**
   *
   *
   * @param minimum
   */
  public final synchronized void waitForWindowSpace(int minimum) {
    /*if (log.isDebugEnabled()) {
      log.debug("Waiting for " + String.valueOf(minimum)
                + " bytes of window space");
    }*/

    while (windowSpace < minimum) {
      try {
        wait(50);
      }
      catch (InterruptedException e) {
      }
    }
  }
}
