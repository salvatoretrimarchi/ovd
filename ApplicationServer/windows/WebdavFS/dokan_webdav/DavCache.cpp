/*
 * Copyright (C) 2010 Ulteo SAS
 * http://www.ulteo.com
 * Author David LECHEVALIER <david@ulteo.com> 2010
 *
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License
 * as published by the Free Software Foundation; version 2
 * of the License.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA  02110-1301, USA.
 */

#include "DavCache.h"
#include "debug.h"

extern BOOL g_UseStdErr;
extern BOOL g_DebugMode;



DavCache::DavCache() {}
DavCache::~DavCache() {}


WCHAR* DavCache::getCacheDir() {
	  BYTE tempDir[MAX_PATH + 1];
	  HKEY hkey;
	  DWORD len;
	  DWORD type;
	  HRESULT err;

	  WCHAR* cacheDir = (WCHAR*)malloc(MAX_PATH * sizeof(WCHAR));

	  err = RegOpenKeyEx(HKEY_CURRENT_USER,
	        L"Software\\Microsoft\\Windows\\CurrentVersion\\Explorer\\"
	        L"Shell Folders", 0, KEY_READ, &hkey);
	  if (err != ERROR_SUCCESS)
	  {
	     return NULL;
	  }
	  len = MAX_PATH;
	  err = RegQueryValueEx(hkey, L"AppData", NULL, &type, tempDir, &len);
	  if (err != ERROR_SUCCESS || len >= MAX_PATH)
	  {
	    RegCloseKey(hkey);
	  	return NULL;
	  }
	  RegCloseKey(hkey);

	  swprintf_s(cacheDir, MAX_PATH, L"%s\\%s", tempDir, DAV_CACHE_DIR_SUFFIXE);
	  return cacheDir;
}

ULONG64 DavCache::getNextEmptyEntry() {
	int i = 0;
	for (i = 0 ; i < DAV_CACHE_SIZE ; i++)
	{
		if (! cache[i].isSet )
		{
			return (ULONG64)i;
		}
	}
	return (ULONG64)-1;
}


int DavCache::init(WebdavServer* server)
{
	int i = 0;
	count = 0;
	DWORD lastError = 0;

	for (i = 0 ; i < count ; i++)
	{
		DAVCACHEENTRY* currentEntry = &cache[i];
		currentEntry->isSet = FALSE;
		currentEntry->needExport = FALSE;
		currentEntry->remotePath[0] = '\0';
		currentEntry->cachePath[0] = '\0';
	}

	cacheDir = getCacheDir();
	if (cacheDir == NULL)
	{
		DbgPrint(L"Unable know where is the cache dir\n");
		return -1;
	}
	if (FAILED(SHCreateDirectoryEx(NULL, cacheDir, NULL))){
		lastError = GetLastError();
		if (lastError == ERROR_PATH_NOT_FOUND) {
			DbgPrint(L"Unable to create %s\n", cacheDir);
		}
	}
	davServer = server;
	return 0;
}


void DavCache::clean(WebdavServer* server)
{
	int i = 0;
	UNREFERENCED_PARAMETER(server);
	for (i = 0 ; i < DAV_CACHE_SIZE ; i++)
	{
		//TODO save unsaved file
		DAVCACHEENTRY* currentEntry = &cache[i];
		if(currentEntry->isSet)
		{
			currentEntry->isSet = FALSE;
			currentEntry->needExport = FALSE;
			currentEntry->remotePath[0] = '\0';
			currentEntry->cachePath[0] = '\0';
		}
	}

	if (cacheDir)
	{
		free(cacheDir);
	}
}

ULONG64 DavCache::add(WCHAR* path)
{
	ULONG64 cacheHandle = (ULONG64)-1;

	if (count > DAV_CACHE_SIZE)
	{
		DbgPrint(L"Enable to add new element to cache, cache is full\n");
		return (ULONG64)-1;
	}
	//TODO multithread operation
	cacheHandle = getNextEmptyEntry();
	cache[cacheHandle].isSet = FALSE;
	cache[cacheHandle].needExport = FALSE;

	wcscpy_s(cache[cacheHandle].remotePath, MAX_PATH, path);
	swprintf_s(cache[cacheHandle].cachePath, MAX_PATH, L"%s\\%u", cacheDir, cacheHandle);

	count++;

	return cacheHandle;
}


BOOL DavCache::remove(ULONG64 handle)
{
	DAVCACHEENTRY* currentEntry = NULL;
	BOOL ret = TRUE;

	if (handle < 0 || handle > DAV_CACHE_SIZE) {
		DbgPrint(L"Enable to remove element from cache, invalid handle\n");
		return FALSE;
	}
	//TODO multithread operation
	currentEntry = &cache[handle];

	if (!DeleteFile(currentEntry->cachePath))
	{
		DbgPrint(L"Error %u while deleting the file %s.\n", GetLastError(), currentEntry->cachePath);
		ret = FALSE;
	}

	currentEntry->cachePath[0] = '\0';
	currentEntry->remotePath[0] = '\0';
	currentEntry->isSet = FALSE;
	currentEntry->needExport = FALSE;

	return ret;
}

DAVCACHEENTRY* DavCache::getFromHandle(ULONG64 handle)
{
	if (handle == -1)
		return NULL;
	return &cache[handle];
}

DAVCACHEENTRY* DavCache::getFromPath(WCHAR* path)
{
	int i = 0;
	for (i = 0 ; i < DAV_CACHE_SIZE ; i++)
	{
		//TODO save unsaved file
		DAVCACHEENTRY* currentEntry = &cache[i];
		if(currentEntry->isSet)
		{
			if (wcscmp(currentEntry->remotePath, path) == 0)
			{
				return currentEntry;
			}
		}
	}
	return NULL;
}

ULONG64 DavCache::getHandleFromPath(WCHAR* path) {
	int i = 0;
	for (i = 0 ; i < DAV_CACHE_SIZE ; i++) {
		//TODO save unsaved file
		DAVCACHEENTRY* currentEntry = &cache[i];
		if(currentEntry->isSet) {
			if ( currentEntry->remotePath!= NULL && wcscmp(currentEntry->remotePath, path) == 0) {
				return i;
			}
		}
	}
	return (ULONG64)-1;
}
