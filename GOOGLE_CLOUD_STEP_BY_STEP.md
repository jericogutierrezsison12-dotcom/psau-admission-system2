# Step-by-Step: Mag-add ng Authorized Network sa Google Cloud SQL

## 📍 Saan Makikita ang "Authorized networks"

### Option 1: Sa Networking Tab (Current View)

1. **Nasa Networking tab ka na** - makikita mo ang:
   - "Public IP connectivity" (Enabled)
   - "Public IP address": `34.170.34.174`

2. **Hanapin ang "Authorized networks"** section:
   - **Scroll down** sa page
   - Hanapin ang section na may label na **"Authorized networks"** or **"Authorized IP addresses"**
   - Usually nasa ibaba ng "Public IP address" section
   - O nasa loob ng "Security" section na nasa baba

3. **Kapag nakita mo na:**
   - May button na **"Add network"** o **"Add IP address"**
   - Click mo yun
   - Enter:
     - **Name**: `Render-All-IPs`
     - **Network**: `0.0.0.0/0`
   - Click **"Done"** then **"Save"**

### Option 2: Sa Connections Tab (Alternative)

Kung hindi mo makita sa Networking tab:

1. **Click sa "Connections"** sa left sidebar (kung available)
2. O kaya mag-click sa **"Summary"** tab, then balik sa **"Networking"** tab
3. Hanapin ulit ang **"Authorized networks"** section

### Option 3: Sa Overview/Summary

1. Click mo ang **"Summary"** tab sa top
2. Scroll down, hanapin ang **"Authorized networks"** section
3. May **"Edit"** or **"Add network"** button doon

## 🎯 Visual Guide - Ano ang Hahanapin:

```
Networking Tab
├── Public IP connectivity: Enabled
├── Public IP address: 34.170.34.174
├── [SCROLL DOWN]
├── Authorized networks          ← ITO ANG HAHANAPIN MO
│   ├── [List ng existing networks kung meron]
│   └── [Add network] button     ← CLICK MO TO
└── Security section
```

## ⚠️ Kung Wala Kang Makita:

1. **Check kung may scroll bar** - baka nasa ibaba pa
2. **Try mag-click sa "Edit"** button kung meron sa Networking tab
3. **Try sa ibang tab**: Security tab, o Connections tab (kung available)

## ✅ After Adding:

1. Maghintay ng **1-2 minutes**
2. Refresh mo ang page
3. Dapat makikita mo na ang `0.0.0.0/0` sa authorized networks list
4. Test mo ulit ang website mo

## 💡 Tip:

Kung wala ka pa ring makita, pwedeng:
- **User permissions**: Baka kailangan ng mas mataas na permission
- **Check sa ibang browser** o mag-refresh
- **Contact admin** kung hindi ka ang owner ng project

